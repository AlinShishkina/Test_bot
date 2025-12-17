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
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('income_id')->index();
            $table->string('number', 100)->nullable();
            $table->date('date')->index();
            $table->date('last_change_date')->nullable();
            $table->string('supplier_article', 100)->nullable();
            $table->string('tech_size', 100)->nullable();
            $table->bigInteger('barcode')->nullable()->index();
            $table->integer('quantity')->default(0);
            $table->decimal('total_price', 15, 2)->nullable();
            $table->date('date_close')->nullable();
            $table->string('warehouse_name', 255)->nullable();
            $table->bigInteger('nm_id')->nullable()->index();
            $table->timestamps();

            $table->index(['date', 'warehouse_name']);
            $table->unique(['income_id', 'barcode', 'warehouse_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
