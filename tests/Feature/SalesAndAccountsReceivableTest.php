<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchUserPermission;
use App\Models\Product;
use App\Models\User;
use App\Services\Erp\InventoryKardexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesAndAccountsReceivableTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_credit_sale_using_wholesale_tier_and_collects_installments(): void
    {
        $user = User::factory()->create();
        $branch = Branch::query()->create(['code' => 'SUC-01', 'name' => 'Central']);
        BranchUserPermission::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'permission' => 'sales.create',
        ]);

        $product = Product::query()->create([
            'sku' => 'SKU-200',
            'name' => 'Producto ERP',
            'base_unit' => 'unit',
            'pack_factor' => 10,
            'retail_price' => 15.00,
            'wholesale_price' => 12.00,
        ]);

        app(InventoryKardexService::class)->adjustStock($branch, $product, 50, 'purchase_in', 'PO-002');

        $sale = $this->actingAs($user)
            ->postJson('/api/v1/erp/sales', [
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_mode' => 'pack', // 10 units * 12.00 = 120.00
                'customer_name' => 'Cliente Corporativo Demo',
                'price_tier' => 'wholesale',
                'payment_term' => 'credit',
            ]);

        $sale->assertCreated()
            ->assertJsonPath('data.total_amount', 120)
            ->assertJsonPath('data.receivable.status', 'open')
            ->assertJsonPath('data.receivable.balance_due', 120);

        $arId = $sale->json('data.receivable.id');

        $this->postJson("/api/v1/erp/receivables/{$arId}/collect", [
            'amount_paid' => 50.00,
            'payment_method' => 'transfer',
            'reference' => 'PAY-1',
        ])->assertOk()
            ->assertJsonPath('data.status', 'partial')
            ->assertJsonPath('data.balance_due', 70);

        // Overpayment rejected
        $this->postJson("/api/v1/erp/receivables/{$arId}/collect", [
            'amount_paid' => 80.00,
        ])->assertUnprocessable();

        $this->postJson("/api/v1/erp/receivables/{$arId}/collect", [
            'amount_paid' => 70.00,
            'reference' => 'PAY-2',
        ])->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.balance_due', 0);
    }
}
