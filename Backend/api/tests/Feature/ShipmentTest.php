<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Pasutijums;
use App\Models\Prece;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_saves_address_snapshot_and_groups_cart_lines(): void
    {
        $buyer = Customer::factory()->create();
        $this->seed();
        $products = Prece::query()->take(2)->get();
        $items = $products->map(fn (Prece $product): array => ['productId' => $product->getKey(), 'quantity' => 2])->all();
        $this->actingAs($buyer)->postJson('/api/checkout', ['items' => $items])->assertUnprocessable()->assertJsonValidationErrors('address');
        $this->assertDatabaseCount('Pasutijums', 0);
        $created = $this->postJson('/api/checkout', ['items' => $items, 'address' => $this->deliveryAddress(), 'saveAddress' => true])->assertCreated();
        $this->assertSame(1, Pasutijums::query()->distinct()->count('shipment_id'));
        $this->assertEquals($this->deliveryAddress(), $buyer->fresh()->delivery_address);
        $otherAddress = array_replace($this->deliveryAddress(), ['street' => 'Otra iela 2']);
        $this->putJson('/api/auth/address', ['address' => $otherAddress])->assertOk();
        $this->getJson('/api/shipments')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $created->json('shipmentId'))->assertJsonPath('0.address.street', 'Testa iela 1')->assertJsonCount(2, '0.items');
    }

    public function test_customer_can_edit_waiting_order_and_cancel_only_once(): void
    {
        $buyer = Customer::factory()->create();
        $this->seed();
        $product = Prece::query()->firstOrFail();
        $stock = $product->Atlikums;
        $created = $this->actingAs($buyer)->postJson('/api/checkout', ['items' => [['productId' => $product->getKey(), 'quantity' => 2]], 'address' => $this->deliveryAddress()])->assertCreated();
        $id = $created->json('shipmentId');
        $line = $created->json('orderIds.0');
        $this->patchJson("/api/shipments/{$id}", ['items' => [['id' => $line, 'quantity' => 3]]])->assertOk();
        $this->assertSame($stock - 3, $product->fresh()->Atlikums);
        $this->patchJson("/api/shipments/{$id}", ['items' => [['id' => $line, 'quantity' => 99]]])->assertUnprocessable();
        $this->assertSame($stock - 3, $product->fresh()->Atlikums);
        $this->patchJson("/api/shipments/{$id}", ['status' => 'atcelts'])->assertOk();
        $this->patchJson("/api/shipments/{$id}", ['status' => 'atcelts'])->assertOk();
        $this->assertSame($stock, $product->fresh()->Atlikums);
        $this->patchJson("/api/shipments/{$id}", ['address' => $this->deliveryAddress()])->assertUnprocessable();
    }

    public function test_ownership_admin_approval_and_shipping_transitions_are_enforced(): void
    {
        config(['app.admin_email' => 'admin@example.com']);
        $buyer = Customer::factory()->create();
        $other = Customer::factory()->create();
        $admin = Customer::factory()->create(['Epasts' => 'admin@example.com']);
        $this->seed();
        $product = Prece::query()->firstOrFail();
        $created = $this->actingAs($buyer)->postJson('/api/checkout', ['items' => [['productId' => $product->getKey(), 'quantity' => 1]], 'address' => $this->deliveryAddress()])->assertCreated();
        $id = $created->json('shipmentId');
        $this->actingAs($other)->getJson('/api/shipments')->assertJsonCount(0);
        $this->patchJson("/api/shipments/{$id}", ['status' => 'atcelts'])->assertNotFound();
        $this->getJson('/api/admin/shipments')->assertForbidden();
        $this->actingAs($buyer)->patchJson("/api/shipments/{$id}", ['status' => 'apstiprinats'])->assertUnprocessable();
        $this->actingAs($admin)->patchJson("/api/admin/shipments/{$id}", ['status' => 'nosutits'])->assertUnprocessable();
        $this->patchJson("/api/admin/shipments/{$id}", ['status' => 'apstiprinats'])->assertOk();
        $this->actingAs($buyer)->patchJson("/api/shipments/{$id}", ['address' => $this->deliveryAddress()])->assertUnprocessable();
        $this->actingAs($admin)->patchJson("/api/admin/shipments/{$id}", ['status' => 'sagatavo'])->assertOk();
        $this->patchJson("/api/admin/shipments/{$id}", ['status' => 'nosutits', 'trackingNumber' => 'TRACK-123'])->assertOk();
        $this->actingAs($buyer)->patchJson("/api/shipments/{$id}", ['status' => 'atcelts'])->assertUnprocessable();
        $this->actingAs($admin)->patchJson("/api/admin/shipments/{$id}", ['status' => 'piegadats'])->assertOk();
        $this->getJson('/api/admin/shipments')->assertJsonPath('0.status', 'piegadats')->assertJsonPath('0.trackingNumber', 'TRACK-123');
    }
}
