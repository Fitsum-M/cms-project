<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P.08 — Page template selection + show-in-navigation flag (SRS 12.3.5, 12.3.8).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('template')->nullable()->after('sort_order')->index();
            $table->boolean('show_in_navigation')->default(false)->after('template')->index();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['template', 'show_in_navigation']);
        });
    }
};
