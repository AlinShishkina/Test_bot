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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('g_number', 50)->index();
            $table->dateTime('date')->index();
            $table->date('last_change_date')->nullable();
            $table->string('supplier_article', 100)->nullable();
            $table->string('tech_size', 100)->nullable();
            $table->bigInteger('barcode')->nullable()->index();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->integer('discount_percent')->nullable();
            $table->string('warehouse_name', 255)->nullable();
            $table->string('oblast', 255)->nullable();
            $table->bigInteger('income_id')->nullable()->index();
            $table->string('odid', 50)->nullable();
            $table->bigInteger('nm_id')->nullable()->index();
            $table->string('subject', 255)->nullable();
            $table->string('category', 255)->nullable();
            $table->string('brand', 255)->nullable();
            $table->boolean('is_cancel')->default(false);
            $table->dateTime('cancel_dt')->nullable();
            $table->timestamps();

            $table->index(['date', 'warehouse_name']);
            $table->unique(['g_number', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
