<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P.06 — Pages core fields + hierarchy support (SRS 12.3.1–12.3.3).
 * Template + show_in_navigation land in P.08.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->longText('body')->nullable()->after('slug');
            $table->foreignId('author_id')
                ->nullable()
                ->after('body')
                ->constrained('users')
                ->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->after('parent_id');

            $table->index(['parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
            $table->dropIndex(['parent_id', 'sort_order']);
            $table->dropColumn(['body', 'author_id', 'sort_order']);
        });
    }
};
