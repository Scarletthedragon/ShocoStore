<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function deliveryAddress(): array
    {
        return ['recipient' => 'Test Buyer', 'phone' => '+37120000000', 'street' => 'Testa iela 1', 'city' => 'Rīga', 'postalCode' => 'LV-1001', 'country' => 'Latvija'];
    }
}
