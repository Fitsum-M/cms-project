<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slug uniqueness is among live (non-trashed) content only — enforced in ContentSlugService.
 * Soft-deleted rows must not block reuse of a slug (SRS restore conflict suffix).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->index('slug');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->unique('slug');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->unique('slug');
        });
    }
};
