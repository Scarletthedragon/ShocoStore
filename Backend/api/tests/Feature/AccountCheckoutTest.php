<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Pasutijums;
use App\Models\Prece;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Mockery\MockInterface;
use Tests\TestCase;

class AccountCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_registration_hashes_password_and_starts_session(): void
    {
        $this->postJson('/api/auth/register', ['name' => 'Buyer', 'email' => 'Buyer@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertCreated()->assertJsonPath('user.email', 'buyer@example.com')->assertJsonPath('user.emailVerified', false)->assertJsonPath('verificationSent', true)->assertJsonMissingPath('user.Parole');
        $customer = Customer::query()->firstOrFail();
        $this->assertTrue(Hash::check('password123', $customer->Parole));
        Notification::assertSentTo($customer, VerifyEmail::class);
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

    public function test_registration_requires_eight_character_password(): void
    {
        $this->postJson('/api/auth/register', ['name' => 'Buyer', 'email' => 'buyer@example.com', 'password' => '1234567', 'password_confirmation' => '1234567'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertDatabaseCount('Lietotajs', 0);
        Notification::assertNothingSent();
        $this->postJson('/api/auth/register', ['name' => 'Buyer', 'email' => 'buyer@example.com', 'password' => '12345678', 'password_confirmation' => '12345678'])->assertCreated();
    }

    public function test_signed_email_link_verifies_the_correct_address_without_a_session(): void
    {
        $customer = Customer::factory()->create(['email_verified_at' => null]);
        $customer->sendEmailVerificationNotification();
        $url = '';
        Notification::assertSentTo($customer, VerifyEmail::class, function (VerifyEmail $notification) use ($customer, &$url): bool {
            $url = $notification->toMail($customer)->actionUrl;

            return $customer->routeNotificationFor('mail', $notification) === $customer->Epasts;
        });
        $this->get($url)->assertRedirect(rtrim(config('app.frontend_url'), '/').'/#/profils');
        $this->assertTrue($customer->fresh()->hasVerifiedEmail());
        $this->assertGuest();
        $this->get($url)->assertRedirect();
    }

    public function test_verification_rejects_expired_tampered_and_wrong_email_links(): void
    {
        $customer = Customer::factory()->create(['email_verified_at' => null]);
        $parameters = ['id' => $customer->getKey(), 'hash' => sha1($customer->Epasts)];
        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), $parameters);
        $this->getJson($expired)->assertForbidden();
        $valid = URL::temporarySignedRoute('verification.verify', now()->addHour(), $parameters);
        $this->getJson($valid.'&tampered=1')->assertForbidden();
        $wrongEmail = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $customer->getKey(), 'hash' => sha1('other@example.com')]);
        $this->getJson($wrongEmail)->assertForbidden();
        $this->assertFalse($customer->fresh()->hasVerifiedEmail());
    }

    public function test_resending_verification_requires_authentication_and_is_rate_limited(): void
    {
        $this->postJson('/api/auth/email/resend')->assertUnauthorized();
        $customer = Customer::factory()->create(['email_verified_at' => null]);
        $this->actingAs($customer)->postJson('/api/auth/email/resend')->assertOk();
        Notification::assertSentTo($customer, VerifyEmail::class);
        $this->postJson('/api/auth/email/resend')->assertTooManyRequests();
    }

    public function test_verified_customers_do_not_receive_another_verification_email(): void
    {
        $this->actingAs(Customer::factory()->create())->postJson('/api/auth/email/resend')->assertOk()->assertJsonPath('alreadyVerified', true);
        Notification::assertNothingSent();
    }

    public function test_changing_email_requires_reverification_and_invalidates_the_old_link(): void
    {
        $customer = Customer::factory()->create();
        $oldUrl = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $customer->getKey(), 'hash' => sha1($customer->Epasts)]);
        $this->actingAs($customer)->postJson('/api/auth/profile', ['name' => 'Buyer', 'email' => 'updated@example.com'])
            ->assertOk()->assertJsonPath('user.emailVerified', false)->assertJsonPath('verificationSent', true);
        $this->assertFalse($customer->fresh()->hasVerifiedEmail());
        Notification::assertSentTo($customer, VerifyEmail::class);
        $this->getJson($oldUrl)->assertForbidden();
    }

    public function test_name_only_changes_keep_email_verified_and_do_not_send_mail(): void
    {
        $customer = Customer::factory()->create();
        $this->actingAs($customer)->postJson('/api/auth/profile', ['name' => 'Buyer', 'email' => $customer->Epasts])
            ->assertOk()->assertJsonPath('user.emailVerified', true);
        Notification::assertNothingSent();
    }

    public function test_unverified_accounts_cannot_checkout_or_access_admin(): void
    {
        config(['app.admin_email' => 'admin@example.com']);
        $customer = Customer::factory()->create(['Epasts' => 'admin@example.com', 'email_verified_at' => null]);
        $this->actingAs($customer)->postJson('/api/checkout', ['items' => []])->assertForbidden();
        $this->getJson('/api/admin/coupons')->assertForbidden();
        $this->getJson('/api/auth/user')->assertOk()->assertJsonPath('user.isAdmin', false);
    }

    public function test_mail_failure_does_not_lose_a_newly_registered_account(): void
    {
        $this->mock(Dispatcher::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        });
        $this->postJson('/api/auth/register', ['name' => 'Buyer', 'email' => 'buyer@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertCreated()->assertJsonPath('verificationSent', false);
        $this->assertDatabaseCount('Lietotajs', 1);
        $this->assertAuthenticated();
    }

    public function test_login_checks_password(): void
    {
        $customer = Customer::factory()->create(['Epasts' => 'buyer@example.com']);
        $this->postJson('/api/auth/login', ['email' => 'buyer@example.com', 'password' => 'wrong'])->assertUnprocessable();
        $this->assertGuest();
        $this->postJson('/api/auth/login', ['email' => 'BUYER@example.com', 'password' => 'password123'])->assertOk();
        $this->assertAuthenticatedAs($customer);
    }

    public function test_profile_updates_persist_and_only_change_the_authenticated_customer(): void
    {
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();
        $this->actingAs($customer)->postJson('/api/auth/profile', [
            'name' => ' Updated Buyer ', 'email' => ' NEW@example.com ', 'id' => $other->getKey(), 'isAdmin' => true,
        ])->assertOk()->assertJsonPath('user.name', 'Updated Buyer')->assertJsonPath('user.email', 'new@example.com')->assertJsonMissingPath('user.Parole');
        $this->assertSame('new@example.com', $customer->fresh()->Epasts);
        $this->assertSame($other->Epasts, $other->fresh()->Epasts);
        $this->getJson('/api/auth/user')->assertOk()->assertJsonPath('user.name', 'Updated Buyer');
        $this->postJson('/api/auth/logout')->assertOk();
        $this->postJson('/api/auth/login', ['email' => 'new@example.com', 'password' => 'password123'])->assertOk();
    }

    public function test_profile_validates_details_and_allows_keeping_own_email(): void
    {
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();
        $this->actingAs($customer)->postJson('/api/auth/profile', ['name' => 'Buyer', 'email' => $customer->Epasts])->assertOk();
        $this->postJson('/api/auth/profile', ['name' => '   ', 'email' => $other->Epasts])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email']);
        $this->postJson('/api/auth/profile', ['name' => str_repeat('a', 101), 'email' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email']);
        $this->assertSame('Buyer', $customer->fresh()->Vards);
        $this->assertSame($customer->Epasts, $customer->fresh()->Epasts);
    }

    public function test_profile_requires_authentication(): void
    {
        $this->postJson('/api/auth/profile', ['name' => 'Buyer', 'email' => 'buyer@example.com'])->assertUnauthorized();
    }

    public function test_checkout_requires_authentication(): void
    {
        $this->postJson('/api/checkout', ['items' => []])->assertUnauthorized();
    }

    public function test_every_order_endpoint_blocks_guests_and_unverified_accounts_without_changing_stock(): void
    {
        $this->seed();
        $product = Prece::query()->firstOrFail();
        $stock = $product->Atlikums;
        $payloads = [
            '/api/orders' => ['productId' => $product->getKey(), 'quantity' => 1, 'emailVerified' => true],
            '/api/checkout' => ['items' => [['productId' => $product->getKey(), 'quantity' => 1]], 'emailVerified' => true],
        ];
        foreach ($payloads as $url => $payload) {
            $this->postJson($url, $payload)->assertUnauthorized();
        }
        $this->actingAs(Customer::factory()->create(['email_verified_at' => null]));
        foreach ($payloads as $url => $payload) {
            $this->postJson($url, $payload)->assertForbidden();
        }
        $this->assertDatabaseCount('Pasutijums', 0);
        $this->assertSame($stock, $product->fresh()->Atlikums);
    }

    public function test_account_deletion_requires_authentication_correct_password_and_confirmation(): void
    {
        $this->deleteJson('/api/auth/account', ['password' => 'password123', 'confirmation' => true])->assertUnauthorized();
        $customer = Customer::factory()->create();
        $this->actingAs($customer)->deleteJson('/api/auth/account', ['password' => 'incorrect', 'confirmation' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->deleteJson('/api/auth/account', ['password' => 'password123', 'confirmation' => false])
            ->assertUnprocessable()->assertJsonValidationErrors('confirmation');
        $this->deleteJson('/api/auth/account', ['confirmation' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertDatabaseHas('Lietotajs', ['Lietotajs_ID' => $customer->getKey()]);
        $this->assertAuthenticatedAs($customer);
    }

    public function test_unverified_customer_can_delete_account_and_cannot_log_back_in_or_use_old_verification_link(): void
    {
        $customer = Customer::factory()->create(['email_verified_at' => null]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $customer->getKey(), 'hash' => sha1($customer->Epasts)]);
        $this->actingAs($customer)->deleteJson('/api/auth/account', ['password' => 'password123', 'confirmation' => true])
            ->assertOk()->assertJsonPath('user', null);
        $this->assertDatabaseMissing('Lietotajs', ['Lietotajs_ID' => $customer->getKey()]);
        $this->assertGuest();
        $this->getJson('/api/auth/user')->assertUnauthorized();
        $this->postJson('/api/auth/login', ['email' => $customer->Epasts, 'password' => 'password123'])->assertUnprocessable();
        $this->getJson($url)->assertNotFound();
    }

    public function test_account_deletion_anonymizes_only_own_orders_and_preserves_stock(): void
    {
        $this->seed();
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();
        $product = Prece::query()->firstOrFail();
        $payload = ['items' => [['productId' => $product->getKey(), 'quantity' => 1]]];
        $this->actingAs($customer)->postJson('/api/checkout', $payload)->assertCreated();
        $this->actingAs($other)->postJson('/api/checkout', $payload)->assertCreated();
        $stock = $product->fresh()->Atlikums;
        $this->actingAs($customer)->deleteJson('/api/auth/account', [
            'password' => 'password123', 'confirmation' => true, 'id' => $other->getKey(),
        ])->assertOk();
        $this->assertGuest();
        $this->assertDatabaseMissing('Lietotajs', ['Lietotajs_ID' => $customer->getKey()]);
        $this->assertDatabaseHas('Lietotajs', ['Lietotajs_ID' => $other->getKey()]);
        $this->assertDatabaseCount('Pasutijums', 2);
        $this->assertDatabaseHas('Pasutijums', ['Lietotajs_ID' => null, 'Klienta_vards' => 'Dzēsts konts']);
        $this->assertDatabaseHas('Pasutijums', ['Lietotajs_ID' => $other->getKey(), 'Klienta_vards' => $other->Vards]);
        $this->assertSame($stock, $product->fresh()->Atlikums);
        $this->assertSame(0, Pasutijums::query()->where('Lietotajs_ID', $customer->getKey())->count());
    }

    public function test_checkout_uses_database_prices_and_applies_discount(): void
    {
        $this->seed();
        $customer = Customer::factory()->create();
        $products = Prece::query()->orderBy('Prece_ID')->take(2)->get();
        $this->actingAs($customer)->postJson('/api/checkout', ['items' => [['productId' => $products[0]->getKey(), 'quantity' => 2], ['productId' => $products[1]->getKey(), 'quantity' => 1]], 'total' => 0.01])->assertCreated()->assertJsonPath('subtotal', 25)->assertJsonPath('total', 25)->assertJsonPath('discount', 0);
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
