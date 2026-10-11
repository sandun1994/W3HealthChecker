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
        Schema::create('scan_issue_remediations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_issue_id')->constrained('scan_issues')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['open', 'planned', 'in_progress', 'implemented', 'verified_by_scan', 'dismissed'])->default('open');
            $table->string('dismissed_reason')->nullable();
            $table->foreignId('verified_scan_id')->nullable()->constrained('scans')->nullOnDelete();
            $table->text('user_notes')->nullable();
            $table->timestamps();

            $table->index(['scan_issue_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_issue_remediations');
    }
};
