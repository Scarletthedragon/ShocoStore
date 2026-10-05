<?php

namespace Database\Seeders;

use App\Models\Prece;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $products = [
            ['Strawberry Energy', 'Šokolāde ar zemeņu garšu', 4.99, 'berry', 'JAUNUMS'],
            ['Banana Energy', 'Šokolāde ar banānu garšu', 4.99, 'banana', 'POPULĀRĀKAIS'],
            ['Mango Energy', 'Šokolāde ar mango garšu', 5.49, 'mango', 'LIMITĒTS'],
        ];

        foreach ($products as [$name, $description, $price, $tone, $tag]) {
            Prece::query()->firstOrCreate(
                ['Nosaukums' => $name],
                [
                    'Cena' => $price,
                    'Atlikums' => 12,
                    'Apraksts' => $description,
                    'Tonis' => $tone,
                    'Birka' => $tag,
                ],
            );
        }
    }
}
