<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_post', function (Blueprint $table) {
            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
                ->cascadeOnDelete();
        });

        Schema::table('post_tag', function (Blueprint $table) {
            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
                ->cascadeOnDelete();
        });

        Schema::table('custom_taxonomy_term_post', function (Blueprint $table) {
            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('category_post', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
        });

        Schema::table('post_tag', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
        });

        Schema::table('custom_taxonomy_term_post', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
        });
    }
};
