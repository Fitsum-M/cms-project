<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->longText('body')->nullable()->after('slug');
            $table->string('excerpt', 500)->nullable()->after('body');
            $table->foreignId('author_id')
                ->nullable()
                ->after('excerpt')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('featured_image_id')
                ->nullable()
                ->after('author_id')
                ->constrained('media_assets')
                ->nullOnDelete();
            $table->string('visibility')->default('public')->after('status')->index();
            $table->string('password')->nullable()->after('visibility');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('featured_image_id');
            $table->dropConstrainedForeignId('author_id');
            $table->dropColumn(['body', 'excerpt', 'visibility', 'password']);
        });
    }
};
