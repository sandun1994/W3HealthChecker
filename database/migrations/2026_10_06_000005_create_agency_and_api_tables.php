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
        // Clients table for Agency multi-client management
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 128);
            $table->string('company', 128)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('notes')->nullable();
            $table->json('white_label_settings')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // Add client_id to websites
        Schema::table('websites', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('project_id')->constrained('clients')->nullOnDelete();
        });

        // Team members table
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Agency owner
            $table->string('member_email', 255);
            $table->string('role', 32)->default('member'); // admin, member, viewer
            $table->string('status', 32)->default('active'); // active, invited
            $table->timestamps();

            $table->unique(['user_id', 'member_email']);
        });

        // API Keys table
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 128);
            $table->string('key_prefix', 16);
            $table->string('key_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedBigInteger('requests_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        // Webhook endpoints table
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('secret', 64);
            $table->json('events');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('team_members');

        Schema::table('websites', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });

        Schema::dropIfExists('clients');
    }
};
