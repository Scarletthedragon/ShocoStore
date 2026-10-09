<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return ['Vards' => fake()->name(), 'Epasts' => fake()->unique()->safeEmail(), 'Parole' => 'password123'];
    }
}
