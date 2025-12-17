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
        Schema::create('collection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postman_collection_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_endpoint_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->enum('type', ['folder', 'request'])->default('request');
            $table->integer('order')->default(0);
            $table->json('raw_data')->nullable(); // Original item data
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('collection_items')->onDelete('cascade');
            $table->index('type');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_items');
    }
};
