<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('g_number', 50)->index();
            $table->date('date')->index();
            $table->date('last_change_date')->nullable();
            $table->string('supplier_article', 100)->nullable();
            $table->string('tech_size', 100)->nullable();
            $table->bigInteger('barcode')->nullable()->index();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->integer('discount_percent')->nullable();
            $table->boolean('is_supply')->nullable();
            $table->boolean('is_realization')->nullable();
            $table->decimal('promo_code_discount', 15, 2)->nullable();
            $table->string('warehouse_name', 255)->nullable();
            $table->string('country_name', 100)->nullable();
            $table->string('oblast_okrug_name', 255)->nullable();
            $table->string('region_name', 255)->nullable();
            $table->bigInteger('income_id')->nullable()->index();
            $table->string('sale_id', 50)->unique();
            $table->string('odid', 50)->nullable();
            $table->decimal('spp', 10, 2)->nullable();
            $table->decimal('for_pay', 15, 2)->nullable();
            $table->decimal('finished_price', 15, 2)->nullable();
            $table->decimal('price_with_disc', 15, 2)->nullable();
            $table->bigInteger('nm_id')->nullable()->index();
            $table->string('subject', 255)->nullable();
            $table->string('category', 255)->nullable();
            $table->string('brand', 255)->nullable();
            $table->boolean('is_storno')->nullable();
            $table->timestamps();

            $table->index(['date', 'warehouse_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
