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
        Schema::create('monitored_websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('schedule', ['daily', 'weekly'])->default('daily')->index();
            $table->string('alert_email')->nullable();
            $table->string('alert_channel', 32)->default('email');
            $table->unsignedTinyInteger('alert_threshold')->default(5);
            $table->boolean('notify_on_critical_issues')->default(true);
            $table->boolean('notify_on_ssl_expiry')->default(true);
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamp('next_scan_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedTinyInteger('consecutive_failures')->default(0);
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('scan_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('current_scan_id')->constrained('scans')->cascadeOnDelete();
            $table->foreignId('previous_scan_id')->nullable()->constrained('scans')->nullOnDelete();
            $table->string('change_type', 64)->index();
            $table->string('category', 32)->index();
            $table->enum('severity', ['critical', 'high', 'medium', 'low', 'info'])->index();
            $table->string('title');
            $table->text('description');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();
        });

        Schema::create('monitoring_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitored_website_id')->constrained('monitored_websites')->cascadeOnDelete();
            $table->foreignId('scan_id')->nullable()->constrained('scans')->nullOnDelete();
            $table->string('channel', 32)->default('email');
            $table->string('recipient');
            $table->string('subject');
            $table->enum('severity', ['critical', 'high', 'medium', 'low', 'info'])->default('high');
            $table->text('message');
            $table->json('changes_summary')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_alerts');
        Schema::dropIfExists('scan_changes');
        Schema::dropIfExists('monitored_websites');
    }
};
