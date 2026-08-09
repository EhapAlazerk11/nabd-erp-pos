<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nabd_api_tokens')) {
            Schema::create('nabd_api_tokens', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->comment('Human-readable label for the token');
                $table->string('token', 64)->unique()->comment('SHA-256 hashed token');
                $table->string('plain_token', 64)->nullable()->comment('Shown once on creation, then cleared');
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nabd_api_tokens');
    }
};
