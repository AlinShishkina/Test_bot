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
        Schema::create('api_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')->constrained()->onDelete('cascade');
            $table->integer('status_code');
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->float('response_time')->nullable(); // in seconds
            $table->timestamp('collected_at');
            $table->boolean('is_successful')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status_code');
            $table->index('collected_at');
            $table->index('is_successful');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_responses');
    }
};
