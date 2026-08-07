<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('structure_type', 20); // hierarchical | flat — immutable after create
            $table->timestamps();
        });

        Schema::create('custom_taxonomy_post_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_taxonomy_id')
                ->constrained('custom_taxonomies')
                ->cascadeOnDelete();
            $table->string('post_type_key', 100);
            $table->timestamps();

            $table->unique(['custom_taxonomy_id', 'post_type_key'], 'custom_tax_post_type_unique');
            $table->index('post_type_key');
        });

        Schema::create('custom_taxonomy_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_taxonomy_id')
                ->constrained('custom_taxonomies')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('custom_taxonomy_terms')
                ->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['custom_taxonomy_id', 'slug'], 'custom_tax_term_slug_unique');
            $table->index('parent_id');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_taxonomy_terms');
        Schema::dropIfExists('custom_taxonomy_post_type');
        Schema::dropIfExists('custom_taxonomies');
    }
};
