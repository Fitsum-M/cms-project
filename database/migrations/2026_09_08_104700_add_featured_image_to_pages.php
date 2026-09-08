<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P.09 — Featured image on pages (same media-library pattern as posts).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('featured_image_id')
                ->nullable()
                ->after('author_id')
                ->constrained('media_assets')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('featured_image_id');
        });
    }
};
