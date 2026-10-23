<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Pasutijums;
use App\Models\Prece;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.admin_email' => 'admin@example.com']);
    }

    public function test_products_can_be_saved_and_listed(): void
    {
        $admin = Customer::factory()->create(['Epasts' => 'admin@example.com']);
        $response = $this->actingAs($admin)->postJson('/api/products', [
            'name' => 'Hazelnut Energy',
            'price' => 5.25,
            'stock' => 8,
            'description' => 'Chocolate with hazelnuts',
            'tone' => 'banana',
            'tag' => 'NEW',
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Hazelnut Energy')
            ->assertJsonPath('price', 5.25)
            ->assertJsonPath('stock', 8);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', (string) $response->json('id'));
    }

    public function test_only_admin_can_create_or_update_chocolates(): void
    {
        $this->postJson('/api/products', [
            'name' => 'Unauthorized chocolate',
            'price' => 5,
            'stock' => 5,
            'tone' => 'berry',
        ])->assertUnauthorized();

        $this->actingAs(Customer::factory()->create(['Epasts' => 'other@example.com']))
            ->postJson('/api/products', [
                'name' => 'Unauthorized chocolate',
                'price' => 5,
                'stock' => 5,
                'tone' => 'berry',
            ])->assertForbidden();

        $admin = Customer::factory()->create(['Epasts' => 'admin@example.com']);
        $this->actingAs($admin)->postJson('/api/products', [
            'name' => 'New chocolate',
            'price' => 4.50,
            'stock' => 10,
            'description' => 'New flavour',
            'tone' => 'matcha',
            'tag' => 'New',
        ])->assertCreated()->assertJsonPath('name', 'New chocolate');

        $product = Prece::query()->firstOrFail();
        $this->actingAs(Customer::factory()->create(['Epasts' => 'buyer@example.com']))
            ->putJson("/api/products/{$product->getKey()}", [
                'name' => 'Unauthorized update',
                'price' => 1,
                'stock' => 1,
                'description' => '',
                'tone' => 'berry',
                'tag' => '',
            ])->assertForbidden();

        $this->actingAs($admin);
        $this->putJson("/api/products/{$product->getKey()}", [
            'name' => 'Updated chocolate',
            'price' => 8.25,
            'stock' => 20,
            'description' => 'Updated flavour',
            'tone' => 'mango',
            'tag' => 'Updated',
        ])->assertOk()->assertJsonPath('name', 'Updated chocolate')->assertJsonPath('price', 8.25);

        $this->assertDatabaseHas('Prece', [
            'Prece_ID' => $product->getKey(),
            'Nosaukums' => 'Updated chocolate',
            'Atlikums' => 20,
        ]);
    }

    public function test_admin_can_upload_and_replace_a_product_image(): void
    {
        Storage::fake('public');
        $admin = Customer::factory()->create(['Epasts' => 'admin@example.com']);

        $created = $this->actingAs($admin)->post('/api/products', [
            'name' => 'Chocolate with image',
            'price' => 6.50,
            'stock' => 4,
            'tone' => 'berry',
            'image' => UploadedFile::fake()->image('first.png')->size(4096),
        ]);

        $created->assertCreated()
            ->assertJsonPath('name', 'Chocolate with image');

        $product = Prece::query()->findOrFail($created->json('id'));
        $oldImagePath = $product->image_path;
        $this->assertNotNull($oldImagePath);
        Storage::disk('public')->assertExists($oldImagePath);
        $this->assertStringEndsWith($oldImagePath, $created->json('imageUrl'));

        $this->actingAs($admin)->post("/api/products/{$product->getKey()}", [
            '_method' => 'PUT',
            'name' => 'Chocolate with replacement image',
            'price' => 6.50,
            'stock' => 4,
            'tone' => 'berry',
            'image' => UploadedFile::fake()->image('replacement.png'),
        ])->assertOk()->assertJsonPath('name', 'Chocolate with replacement image');

        $newImagePath = $product->fresh()->image_path;
        $this->assertNotSame($oldImagePath, $newImagePath);
        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($newImagePath);
    }

    public function test_admin_cannot_save_product_when_selected_image_was_not_received(): void
    {
        $admin = Customer::factory()->create(['Epasts' => 'admin@example.com']);

        $this->actingAs($admin)->postJson('/api/products', [
            'name' => 'Chocolate with missing image',
            'price' => 6.50,
            'stock' => 4,
            'tone' => 'berry',
            'imageSelected' => '1',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        $this->assertDatabaseMissing('Prece', ['Nosaukums' => 'Chocolate with missing image']);
    }

    public function test_admin_can_delete_a_chocolate_and_its_image(): void
    {
        Storage::fake('public');
        $admin = Customer::factory()->create(['Epasts' => 'admin@example.com']);
        $created = $this->actingAs($admin)->post('/api/products', [
            'name' => 'Chocolate to delete',
            'price' => 6.50,
            'stock' => 4,
            'tone' => 'berry',
            'image' => UploadedFile::fake()->image('chocolate.png'),
        ])->assertCreated();
        $product = Prece::query()->findOrFail($created->json('id'));
        $imagePath = $product->image_path;
        Storage::disk('public')->assertExists($imagePath);

        $this->deleteJson("/api/products/{$product->getKey()}")
            ->assertOk()
            ->assertJsonPath('id', (string) $product->getKey());

        $this->assertDatabaseMissing('Prece', ['Prece_ID' => $product->getKey()]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_admin_cannot_delete_a_chocolate_with_orders(): void
    {
        $admin = Customer::factory()->create(['Epasts' => 'admin@example.com']);
        $product = $this->makeProduct(stock: 5);
        Pasutijums::query()->create([
            'Lietotajs_ID' => null,
            'Prece_ID' => $product->Prece_ID,
            'Daudzums' => 1,
            'Kopeja_cena' => 5.25,
            'Statuss' => 'gaida',
            'Klienta_vards' => 'Test buyer',
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/products/{$product->getKey()}")
            ->assertStatus(409)
            ->assertJsonPath('error', 'Šokolādi nevar dzēst, jo tai ir piesaistīti pasūtījumi.');

        $this->assertDatabaseHas('Prece', ['Prece_ID' => $product->getKey()]);
    }

    public function test_catalog_products_include_chocolate_specific_details(): void
    {
        Prece::query()->create([
            'Nosaukums' => 'Zemeņu krēms',
            'Cena' => 7,
            'Atlikums' => 5,
            'Apraksts' => 'Piena šokolāde ar zemeņu krēmu.',
            'Tonis' => 'berry',
            'Birka' => 'Berry best',
        ]);
        Prece::query()->create([
            'Nosaukums' => 'Sāls + Olīveļļa',
            'Cena' => 11,
            'Atlikums' => 5,
            'Apraksts' => 'Tumšā šokolāde ar jūras sāli.',
            'Tonis' => 'salt',
            'Birka' => 'Sigma pick',
        ]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.details.cocoaPercentage', 35)
            ->assertJsonPath('0.details.allergens.0', 'piens')
            ->assertJsonPath('1.details.cocoaPercentage', 72)
            ->assertJsonPath('1.details.nutrition.salt', 0.62);
    }

    public function test_removed_chocolates_are_not_listed(): void
    {
        foreach (['Burbuļu sapnis', 'Melleņu bomba', 'Skarbā vafeļu šokolāde', 'Zemeņu krēms'] as $name) {
            Prece::query()->create([
                'Nosaukums' => $name,
                'Cena' => 9,
                'Atlikums' => 5,
                'Apraksts' => 'Chocolate',
                'Tonis' => 'yellow',
                'Birka' => '',
            ]);
        }

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Zemeņu krēms');
    }

    public function test_order_saves_total_and_decrements_stock(): void
    {
        $product = $this->makeProduct(stock: 5);

        $this->postJson('/api/orders', [
            'productId' => (string) $product->Prece_ID,
            'quantity' => 2,
            'customerName' => 'Test buyer',
        ])
            ->assertCreated()
            ->assertJsonPath('total', 10.5)
            ->assertJsonPath('customerName', 'Test buyer');

        $this->assertSame(3, $product->fresh()->Atlikums);
        $this->assertDatabaseHas('Pasutijums', [
            'Prece_ID' => $product->Prece_ID,
            'Daudzums' => 2,
            'Kopeja_cena' => 10.50,
            'Klienta_vards' => 'Test buyer',
        ]);
    }

    public function test_order_is_rejected_when_stock_is_insufficient(): void
    {
        $product = $this->makeProduct(stock: 1);

        $this->postJson('/api/orders', [
            'productId' => (string) $product->Prece_ID,
            'quantity' => 2,
        ])
            ->assertStatus(409)
            ->assertJsonPath('error', 'Not enough stock.');

        $this->assertSame(1, $product->fresh()->Atlikums);
        $this->assertSame(0, Pasutijums::query()->count());
    }

    private function makeProduct(int $stock): Prece
    {
        return Prece::query()->create([
            'Nosaukums' => 'Test Chocolate',
            'Cena' => 5.25,
            'Atlikums' => $stock,
            'Apraksts' => 'Test product',
            'Tonis' => 'mango',
            'Birka' => '',
        ]);
    }
}
