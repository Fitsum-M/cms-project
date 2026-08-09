<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P.09 — Featured image FK: restrict delete so media cannot vanish while referenced
 * without going through MediaDeletionService (block or Admin force-clear).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['featured_image_id']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreign('featured_image_id')
                ->references('id')
                ->on('media_assets')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['featured_image_id']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreign('featured_image_id')
                ->references('id')
                ->on('media_assets')
                ->nullOnDelete();
        });
    }
};
