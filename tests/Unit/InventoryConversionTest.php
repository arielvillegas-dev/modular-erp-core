<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\Erp\InventoryConversionService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InventoryConversionTest extends TestCase
{
    public function test_converts_pack_and_base_units_accurately(): void
    {
        $product = new Product(['pack_factor' => 12]);
        $service = new InventoryConversionService();

        $this->assertSame(24, $service->toBaseUnits($product, 2, 'pack'));
        $this->assertSame(5, $service->toBaseUnits($product, 5, 'base'));
    }

    public function test_rejects_non_positive_quantities(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new InventoryConversionService())->toBaseUnits(new Product(['pack_factor' => 12]), 0, 'pack');
    }
}
