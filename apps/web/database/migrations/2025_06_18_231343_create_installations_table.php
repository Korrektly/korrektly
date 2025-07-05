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
        Schema::create('installations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('identifier')->unique()->index();
            $table->longText('url')->nullable();
            $table->foreignUuid('app_id')->constrained('apps')->cascadeOnDelete();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('version')->nullable();
            $table->string('ip_address')->nullable();
            $table->longText('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installations');
    }
};
