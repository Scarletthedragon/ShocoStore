<?php

namespace Database\Seeders;

use App\Models\Prece;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $products = json_decode(file_get_contents(database_path('seeders/catalog.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($products as $product) {
            Prece::query()->firstOrCreate(['Nosaukums' => $product['name']], ['Cena' => $product['price'], 'Atlikums' => 25, 'Apraksts' => $product['description'], 'Tonis' => $product['id'], 'Birka' => $product['tag']]);
        }
    }
}
