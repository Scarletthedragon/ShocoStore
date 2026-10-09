<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Prece;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_hashes_password_and_starts_session(): void
    {
        $this->postJson('/api/auth/register', ['name' => 'Buyer', 'email' => 'Buyer@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertCreated()->assertJsonPath('user.email', 'buyer@example.com')->assertJsonMissingPath('user.Parole');
        $customer = Customer::query()->firstOrFail();
        $this->assertTrue(Hash::check('password123', $customer->Parole));
        $this->assertAuthenticatedAs($customer);
        $this->getJson('/api/auth/user')->assertOk()->assertJsonPath('user.name', 'Buyer');
        $this->postJson('/api/auth/logout')->assertOk();
        $this->assertGuest();
        $this->getJson('/api/auth/user')->assertUnauthorized();
    }

    public function test_registration_rejects_duplicate_email_and_unconfirmed_password(): void
    {
        Customer::factory()->create(['Epasts' => 'buyer@example.com']);
        $this->postJson('/api/auth/register', ['name' => 'Buyer', 'email' => 'buyer@example.com', 'password' => 'password123', 'password_confirmation' => 'otherpassword'])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->assertDatabaseCount('Lietotajs', 1);
    }

    public function test_login_checks_password(): void
    {
        $customer = Customer::factory()->create(['Epasts' => 'buyer@example.com']);
        $this->postJson('/api/auth/login', ['email' => 'buyer@example.com', 'password' => 'wrong'])->assertUnprocessable();
        $this->assertGuest();
        $this->postJson('/api/auth/login', ['email' => 'BUYER@example.com', 'password' => 'password123'])->assertOk();
        $this->assertAuthenticatedAs($customer);
    }

    public function test_checkout_requires_authentication(): void
    {
        $this->postJson('/api/checkout', ['items' => []])->assertUnauthorized();
    }

    public function test_checkout_uses_database_prices_and_applies_discount(): void
    {
        $this->seed();
        $customer = Customer::factory()->create();
        $products = Prece::query()->orderBy('Prece_ID')->take(2)->get();
        $this->actingAs($customer)->postJson('/api/checkout', ['items' => [['productId' => $products[0]->getKey(), 'quantity' => 2], ['productId' => $products[1]->getKey(), 'quantity' => 1]], 'coupon' => 'sigma15', 'total' => 0.01])->assertCreated()->assertJsonPath('subtotal', 25)->assertJsonPath('total', 21.25)->assertJsonPath('discount', 3.75);
        $this->assertSame(23, $products[0]->fresh()->Atlikums);
        $this->assertSame(24, $products[1]->fresh()->Atlikums);
        $this->assertDatabaseCount('Pasutijums', 2);
        $this->getJson('/api/orders')->assertOk()->assertJsonCount(2);
        $this->actingAs(Customer::factory()->create())->getJson('/api/orders')->assertOk()->assertJsonCount(0);
        $this->assertDatabaseHas('Pasutijums', ['Lietotajs_ID' => $customer->getKey(), 'Klienta_vards' => $customer->Vards]);
    }

    public function test_checkout_is_atomic_when_one_product_is_out_of_stock(): void
    {
        $this->seed();
        $products = Prece::query()->orderBy('Prece_ID')->take(2)->get();
        $products[1]->update(['Atlikums' => 0]);
        $this->actingAs(Customer::factory()->create())->postJson('/api/checkout', ['items' => [['productId' => $products[0]->getKey(), 'quantity' => 1], ['productId' => $products[1]->getKey(), 'quantity' => 1]]])->assertUnprocessable();
        $this->assertSame(25, $products[0]->fresh()->Atlikums);
        $this->assertDatabaseCount('Pasutijums', 0);
    }

    public function test_checkout_rejects_duplicate_items_and_invalid_coupon(): void
    {
        $this->seed();
        $id = Prece::query()->firstOrFail()->getKey();
        $this->actingAs(Customer::factory()->create())->postJson('/api/checkout', ['items' => [['productId' => $id, 'quantity' => 1], ['productId' => $id, 'quantity' => 1]], 'coupon' => 'INVALID'])->assertUnprocessable()->assertJsonValidationErrors(['coupon', 'items.0.productId']);
        $this->assertDatabaseCount('Pasutijums', 0);
    }

    public function test_catalog_returns_visuals_and_stock(): void
    {
        $this->seed();
        $this->getJson('/api/products')->assertOk()->assertJsonCount(9)->assertJsonPath('0.tile', 0)->assertJsonPath('2.category', "Balt\u{0101}")->assertJsonPath('0.stock', 25);
    }
}
