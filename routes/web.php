<?php

use App\Models\AccountsReceivable;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('erp_dashboard', [
        'branches' => Branch::query()->orderBy('code')->get(),
        'stocks' => BranchStock::query()->with(['branch', 'product'])->get(),
        'invoices' => SalesInvoice::query()->with('receivable')->latest('id')->get(),
        'receivablesCount' => AccountsReceivable::query()->where('status', '!=', 'paid')->count(),
    ]);
});
