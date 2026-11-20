<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\BranchUserPermission;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Erp\InventoryKardexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchStockTransferAndPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfers_pack_units_between_branches_with_kardex_trail_and_permission_isolation(): void
    {
        $user = User::factory()->create();
        $main = Branch::query()->create(['code' => 'SUC-01', 'name' => 'Central']);
        $north = Branch::query()->create(['code' => 'SUC-02', 'name' => 'Norte']);
        $prod = Product::query()->create([
            'sku' => 'SKU-100',
            'name' => 'Caja Demo',
            'base_unit' => 'unit',
            'pack_factor' => 12,
            'retail_price' => 10.00,
            'wholesale_price' => 8.00,
        ]);

        app(InventoryKardexService::class)->adjustStock($main, $prod, 36, 'purchase_in', 'PO-001');

        // Without branch permission -> 403
        $this->actingAs($user)
            ->postJson('/api/v1/erp/transfers', [
                'from_branch_id' => $main->id,
                'to_branch_id' => $north->id,
                'product_id' => $prod->id,
                'quantity' => 2,
                'unit_mode' => 'pack',
                'reference' => 'TR-001',
            ])
            ->assertForbidden();

        // Grant permission only for $main
        BranchUserPermission::query()->create([
            'user_id' => $user->id,
            'branch_id' => $main->id,
            'permission' => 'inventory.transfer',
        ]);

        // Transfer 2 packs (24 base units)
        $this->actingAs($user)
            ->postJson('/api/v1/erp/transfers', [
                'from_branch_id' => $main->id,
                'to_branch_id' => $north->id,
                'product_id' => $prod->id,
                'quantity' => 2,
                'unit_mode' => 'pack',
                'reference' => 'TR-001',
            ])
            ->assertOk()
            ->assertJsonPath('quantity_base', 24);

        $this->assertSame(12, BranchStock::query()->where('branch_id', $main->id)->firstOrFail()->quantity_base);
        $this->assertSame(24, BranchStock::query()->where('branch_id', $north->id)->firstOrFail()->quantity_base);
        $this->assertSame(3, StockMovement::query()->count());

        // Prevent negative stock transfer
        $this->actingAs($user)
            ->postJson('/api/v1/erp/transfers', [
                'from_branch_id' => $main->id,
                'to_branch_id' => $north->id,
                'product_id' => $prod->id,
                'quantity' => 2,
                'unit_mode' => 'pack',
                'reference' => 'TR-OVER',
            ])
            ->assertUnprocessable();
    }
}
