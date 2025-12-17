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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->date('last_change_date')->nullable();
            $table->string('supplier_article', 100)->nullable();
            $table->string('tech_size', 100)->nullable();
            $table->bigInteger('barcode')->nullable()->index();
            $table->integer('quantity')->default(0);
            $table->boolean('is_supply')->nullable();
            $table->boolean('is_realization')->nullable();
            $table->integer('quantity_full')->nullable();
            $table->string('warehouse_name', 255)->nullable();
            $table->integer('in_way_to_client')->nullable();
            $table->integer('in_way_from_client')->nullable();
            $table->bigInteger('nm_id')->nullable()->index();
            $table->string('subject', 255)->nullable();
            $table->string('category', 255)->nullable();
            $table->string('brand', 255)->nullable();
            $table->bigInteger('sc_code')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->integer('discount')->nullable();
            $table->timestamps();

            $table->index(['date', 'warehouse_name']);
            $table->unique(['date', 'barcode', 'warehouse_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
