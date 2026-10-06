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
        Schema::table('scan_metrics', function (Blueprint $table) {
            $table->json('meta_summary')->nullable()->after('structured_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scan_metrics', function (Blueprint $table) {
            $table->dropColumn('meta_summary');
        });
    }
};
