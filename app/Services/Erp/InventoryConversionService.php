<?php

namespace App\Services\Erp;

use App\Models\Product;
use InvalidArgumentException;

class InventoryConversionService
{
    /**
     * Converts pack or base quantities into normalized base inventory units.
     */
    public function toBaseUnits(Product $product, int $quantity, string $unitMode = 'base'): int
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        return match ($unitMode) {
            'base' => $quantity,
            'pack' => $quantity * max(1, (int) $product->pack_factor),
            default => throw new InvalidArgumentException("Unsupported unit mode: {$unitMode}"),
        };
    }
}
