<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GAP.NFR.01 — FULLTEXT indexes for post/page title+body search (SRS §6, §19.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->fullText(['title', 'excerpt', 'body'], 'posts_content_fulltext');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->fullText(['title', 'body'], 'pages_content_fulltext');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropFullText('posts_content_fulltext');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropFullText('pages_content_fulltext');
        });
    }
};
