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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32); // slack, github, webhook, email
            $table->string('name', 64);
            $table->text('encrypted_credentials'); // encrypted JSON credentials (webhook URL, personal token, etc.)
            $table->json('settings')->nullable(); // channel, repository, event filters
            $table->string('status', 32)->default('connected'); // connected, degraded, revoked
            $table->timestamp('last_dispatched_at')->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'provider', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
