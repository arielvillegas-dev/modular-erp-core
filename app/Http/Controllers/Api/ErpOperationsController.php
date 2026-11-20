<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountsReceivable;
use App\Models\Branch;
use App\Models\Product;
use App\Services\Erp\InventoryConversionService;
use App\Services\Erp\InventoryKardexService;
use App\Services\Erp\SalesAndReceivablesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ErpOperationsController extends Controller
{
    public function transfer(
        Request $request,
        InventoryConversionService $converter,
        InventoryKardexService $kardex
    ): JsonResponse {
        $validated = $request->validate([
            'from_branch_id' => ['required', 'exists:branches,id'],
            'to_branch_id' => ['required', 'exists:branches,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_mode' => ['sometimes', 'in:base,pack'],
            'reference' => ['required', 'string', 'max:64'],
        ]);

        $from = Branch::query()->findOrFail($validated['from_branch_id']);
        $to = Branch::query()->findOrFail($validated['to_branch_id']);
        $product = Product::query()->findOrFail($validated['product_id']);
        $qtyBase = $converter->toBaseUnits($product, (int) $validated['quantity'], $validated['unit_mode'] ?? 'base');

        try {
            $kardex->transferBetweenBranches($from, $to, $product, $qtyBase, $validated['reference']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'transferred', 'quantity_base' => $qtyBase]);
    }

    public function createSale(
        Request $request,
        InventoryConversionService $converter,
        SalesAndReceivablesService $sales
    ): JsonResponse {
        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_mode' => ['sometimes', 'in:base,pack'],
            'customer_name' => ['required', 'string', 'max:120'],
            'price_tier' => ['sometimes', 'in:retail,wholesale'],
            'payment_term' => ['sometimes', 'in:cash,credit'],
        ]);

        $branch = Branch::query()->findOrFail($validated['branch_id']);
        $product = Product::query()->findOrFail($validated['product_id']);
        $qtyBase = $converter->toBaseUnits($product, (int) $validated['quantity'], $validated['unit_mode'] ?? 'base');

        try {
            $invoice = $sales->createSale(
                $branch,
                $product,
                $qtyBase,
                $validated['customer_name'],
                $validated['price_tier'] ?? 'retail',
                $validated['payment_term'] ?? 'cash'
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $invoice], 201);
    }

    public function collectReceivable(
        AccountsReceivable $receivable,
        Request $request,
        SalesAndReceivablesService $sales
    ): JsonResponse {
        $validated = $request->validate([
            'amount_paid' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['sometimes', 'string', 'max:24'],
            'reference' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $updated = $sales->recordInstallment(
                $receivable,
                (float) $validated['amount_paid'],
                $validated['payment_method'] ?? 'transfer',
                $validated['reference'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $updated]);
    }
}
