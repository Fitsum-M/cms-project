<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assignment pivots for taxonomy ↔ content (SRS 13.1.5 / T.04).
     * post_id is unsigned without FK until Posts (Phase 5) exist.
     */
    public function up(): void
    {
        Schema::create('category_post', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->unsignedBigInteger('post_id');
            $table->timestamps();

            $table->unique(['category_id', 'post_id']);
            $table->index('post_id');
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->unsignedBigInteger('post_id');
            $table->timestamps();

            $table->unique(['tag_id', 'post_id']);
            $table->index('post_id');
        });

        Schema::create('custom_taxonomy_term_post', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_taxonomy_term_id')
                ->constrained('custom_taxonomy_terms')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('post_id');
            $table->timestamps();

            $table->unique(['custom_taxonomy_term_id', 'post_id'], 'ct_term_post_unique');
            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_taxonomy_term_post');
        Schema::dropIfExists('post_tag');
        Schema::dropIfExists('category_post');
    }
};
