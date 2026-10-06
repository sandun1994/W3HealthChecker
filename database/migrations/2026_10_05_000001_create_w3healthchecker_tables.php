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
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('domain')->index();
            $table->string('scheme', 10)->default('https');
            $table->text('canonical_url');
            $table->boolean('is_monitored')->default(false);
            $table->enum('monitoring_frequency', ['daily', 'weekly', 'monthly'])->nullable();
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('public_id', 64)->unique()->index();
            $table->text('target_url');
            $table->text('final_url')->nullable();
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued')->index();
            $table->string('status_stage', 64)->default('Website reachable');
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->integer('http_status_code')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->string('status_label', 32)->nullable();
            
            // Pillar Category Scores (0 - 100)
            $table->unsignedTinyInteger('score_seo')->nullable();
            $table->unsignedTinyInteger('score_performance')->nullable();
            $table->unsignedTinyInteger('score_security')->nullable();
            $table->unsignedTinyInteger('score_accessibility')->nullable();
            $table->unsignedTinyInteger('score_mobile')->nullable();
            $table->unsignedTinyInteger('score_technical')->nullable();
            $table->unsignedTinyInteger('score_ai_readiness')->nullable();

            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_rules', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->string('category', 32)->index();
            $table->string('title');
            $table->enum('severity', ['critical', 'high', 'medium', 'low', 'info'])->index();
            $table->decimal('default_weight', 4, 2)->default(1.0);
            $table->text('impact_description');
            $table->text('why_it_matters');
            $table->text('fix_guidance');
            $table->text('technical_fix_guidance')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('scan_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->string('rule_id', 64);
            $table->string('category', 32)->index();
            $table->enum('severity', ['critical', 'high', 'medium', 'low', 'info'])->index();
            $table->enum('confidence', ['high', 'medium', 'low'])->default('high');
            $table->string('title');
            $table->text('affected_resource')->nullable();
            $table->json('evidence')->nullable();
            $table->text('why_it_matters');
            $table->text('recommendation');
            $table->text('technical_details')->nullable();
            $table->unsignedSmallInteger('priority_order')->default(0);
            $table->timestamps();

            $table->foreign('rule_id')->references('id')->on('audit_rules');
        });

        Schema::create('scan_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete()->unique();
            $table->json('headers')->nullable();
            $table->json('ssl_data')->nullable();
            $table->json('dns_records')->nullable();
            $table->json('open_graph')->nullable();
            $table->json('twitter_card')->nullable();
            $table->json('structured_data')->nullable();
            $table->json('headings_summary')->nullable();
            $table->json('links_summary')->nullable();
            $table->json('assets_summary')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_metrics');
        Schema::dropIfExists('scan_issues');
        Schema::dropIfExists('audit_rules');
        Schema::dropIfExists('scans');
        Schema::dropIfExists('websites');
    }
};
