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
        Schema::create('api_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('method', 10)->default('GET');
            $table->string('url');
            $table->json('headers')->nullable();
            $table->json('query_params')->nullable();
            $table->json('body')->nullable();
            $table->string('source')->default('manual'); // manual, github, postman
            $table->string('source_reference')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('source');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_endpoints');
    }
};
