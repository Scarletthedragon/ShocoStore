<?php

namespace Tests\Feature;

use App\Models\Pasutijums;
use App\Models\Prece;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_can_be_saved_and_listed(): void
    {
        $response = $this->postJson('/api/products', [
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