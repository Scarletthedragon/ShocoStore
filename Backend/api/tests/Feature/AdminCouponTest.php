<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DiscountCoupon;
use App\Models\Prece;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.admin_email' => 'admin@example.com']);
    }

    public function test_coupon_management_requires_admin_account(): void
    {
        $this->getJson('/api/admin/coupons')->assertUnauthorized();

        $this->actingAs(Customer::factory()->create(['Epasts' => 'buyer@example.com']))
            ->getJson('/api/admin/coupons')
            ->assertForbidden();

        $this->actingAs(Customer::factory()->create(['Epasts' => 'admin@example.com']))
            ->getJson('/api/admin/coupons')
            ->assertOk();
    }

    public function test_admin_creates_one_use_code_and_checkout_redeems_it_once(): void
    {
        $admin = Customer::factory()->create(['Epasts' => 'admin@example.com']);
        $customer = Customer::factory()->create();
        $this->seed();
        $product = Prece::query()->firstOrFail();

        $this->actingAs($admin)->postJson('/api/admin/coupons', [
            'code' => ' summer20 ',
            'discountPercent' => 20,
            'usageLimit' => 1,
            'expiresAt' => now()->addDay()->toISOString(),
        ])->assertCreated()->assertJsonPath('code', 'SUMMER20')->assertJsonPath('usage_count', 0);

        $this->getJson('/api/coupons/validate?code=summer20&productIds[]='.$product->getKey())
            ->assertOk()
            ->assertJsonPath('discountPercent', 20);

        $this->actingAs($customer)->postJson('/api/checkout', [
            'items' => [['productId' => $product->getKey(), 'quantity' => 1]],
            'coupon' => 'summer20',
        ])->assertCreated()->assertJsonPath('total', 5.6)->assertJsonPath('discount', 1.4);

        $this->assertDatabaseHas('discount_coupons', ['code' => 'SUMMER20', 'usage_count' => 1]);
        $this->postJson('/api/checkout', [
            'items' => [['productId' => $product->getKey(), 'quantity' => 1]],
            'coupon' => 'SUMMER20',
        ])->assertUnprocessable()->assertJsonValidationErrors('coupon');
        $this->assertSame(24, $product->fresh()->Atlikums);
    }

    public function test_unlimited_code_can_be_redeemed_more_than_once(): void
    {
        $this->actingAs(Customer::factory()->create(['Epasts' => 'admin@example.com']))
            ->postJson('/api/admin/coupons', [
                'code' => 'ALWAYS',
                'discountPercent' => 12.5,
                'usageLimit' => 0,
                'expiresAt' => now()->addDay()->toISOString(),
            ])->assertCreated()->assertJsonPath('usage_limit', null);

        $this->seed();
        $product = Prece::query()->firstOrFail();
        $customer = Customer::factory()->create();

        foreach ([1, 2] as $unused) {
            $this->actingAs($customer)->postJson('/api/checkout', [
                'items' => [['productId' => $product->getKey(), 'quantity' => 1]],
                'coupon' => 'always',
            ])->assertCreated()->assertJsonPath('discountPercent', 12.5);
        }

        $this->assertDatabaseHas('discount_coupons', ['code' => 'ALWAYS', 'usage_count' => 2]);
    }

    public function test_expired_and_revoked_codes_cannot_be_applied(): void
    {
        $admin = Customer::factory()->create(['Epasts' => 'admin@example.com']);
        $coupon = DiscountCoupon::query()->create([
            'code' => 'EXPIRED',
            'discount_percent' => 15,
            'usage_limit' => null,
            'usage_count' => 0,
            'expires_at' => now()->subMinute(),
        ]);

        $this->getJson('/api/coupons/validate?code=EXPIRED')->assertUnprocessable();
        $this->actingAs($admin)->deleteJson("/api/admin/coupons/{$coupon->getKey()}")->assertOk()->assertJsonPath('active', false);
        $this->getJson('/api/coupons/validate?code=EXPIRED')->assertUnprocessable();
    }

    public function test_coupon_can_be_limited_to_selected_chocolates(): void
    {
        $admin = Customer::factory()->create(['Epasts' => 'admin@example.com']);
        $customer = Customer::factory()->create();
        $this->seed();
        $products = Prece::query()->orderBy('Prece_ID')->take(2)->get();

        $this->actingAs($admin)->postJson('/api/admin/coupons', [
            'code' => 'BERRYONLY',
            'discountPercent' => 50,
            'usageLimit' => 0,
            'expiresAt' => now()->addDay()->toISOString(),
            'productIds' => [$products[0]->getKey()],
        ])->assertCreated()->assertJsonCount(1, 'products');

        $this->getJson('/api/coupons/validate?code=BERRYONLY&productIds[]='.$products[1]->getKey())
            ->assertUnprocessable()->assertJsonValidationErrors('coupon');
        $this->getJson('/api/coupons/validate?code=BERRYONLY&productIds[]='.$products[0]->getKey())
            ->assertOk()->assertJsonPath('productIds.0', (string) $products[0]->getKey());

        $this->actingAs($customer)->postJson('/api/checkout', [
            'items' => [
                ['productId' => $products[0]->getKey(), 'quantity' => 1],
                ['productId' => $products[1]->getKey(), 'quantity' => 1],
            ],
            'coupon' => 'BERRYONLY',
        ])->assertCreated()->assertJsonPath('subtotal', 18)->assertJsonPath('discount', 3.5)->assertJsonPath('total', 14.5);
    }
}
