<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('branch_user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('permission', 64);
            $table->timestamps();

            $table->unique(['user_id', 'branch_id', 'permission'], 'branch_user_perm_unique');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 32)->unique();
            $table->string('name', 120);
            $table->string('base_unit', 16)->default('unit');
            $table->unsignedInteger('pack_factor')->default(1);
            $table->decimal('retail_price', 10, 2);
            $table->decimal('wholesale_price', 10, 2);
            $table->timestamps();
        });

        Schema::create('branch_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedBigInteger('quantity_base')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'product_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('type', 24); // purchase_in | transfer_out | transfer_in | sale_out
            $table->integer('delta_base');
            $table->unsignedBigInteger('balance_after');
            $table->string('reference', 64);
            $table->timestamps();
        });

        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('invoice_number', 32)->unique();
            $table->string('customer_name', 120);
            $table->string('price_tier', 16)->default('retail');
            $table->string('payment_term', 16)->default('cash'); // cash | credit
            $table->decimal('total_amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->decimal('original_amount', 12, 2);
            $table->decimal('balance_due', 12, 2);
            $table->string('status', 16)->default('open'); // open | partial | paid
            $table->timestamps();
        });

        Schema::create('ar_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounts_receivable_id')->constrained('accounts_receivable')->cascadeOnDelete();
            $table->decimal('amount_paid', 12, 2);
            $table->string('payment_method', 24)->default('transfer');
            $table->string('reference', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_installments');
        Schema::dropIfExists('accounts_receivable');
        Schema::dropIfExists('sales_invoices');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('branch_stocks');
        Schema::dropIfExists('products');
        Schema::dropIfExists('branch_user_permissions');
        Schema::dropIfExists('branches');
    }
};
