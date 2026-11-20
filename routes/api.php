<?php

use App\Http\Controllers\Api\ErpOperationsController;
use App\Http\Middleware\RequireBranchPermission;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/erp')->group(function () {
    Route::post('/transfers', [ErpOperationsController::class, 'transfer'])
        ->middleware(RequireBranchPermission::class.':inventory.transfer');
    Route::post('/sales', [ErpOperationsController::class, 'createSale'])
        ->middleware(RequireBranchPermission::class.':sales.create');
    Route::post('/receivables/{receivable}/collect', [ErpOperationsController::class, 'collectReceivable']);
});
