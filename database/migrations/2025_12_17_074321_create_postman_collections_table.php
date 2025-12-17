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
        Schema::create('postman_collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('postman_id')->nullable();
            $table->text('description')->nullable();
            $table->string('schema_version')->nullable();
            $table->json('variables')->nullable();
            $table->json('auth')->nullable();
            $table->json('raw_data')->nullable(); // Original JSON data
            $table->string('source_url')->nullable();
            $table->timestamps();

            $table->index('postman_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postman_collections');
    }
};
