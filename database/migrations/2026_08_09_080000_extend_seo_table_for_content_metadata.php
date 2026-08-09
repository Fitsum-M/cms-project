<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo', function (Blueprint $table) {
            $table->string('focus_keyword', 100)->nullable()->after('canonical_url');
            $table->string('og_title')->nullable()->after('focus_keyword');
            $table->text('og_description')->nullable()->after('og_title');
            $table->foreignId('og_image_id')
                ->nullable()
                ->after('og_description')
                ->constrained('media_assets')
                ->nullOnDelete();
            $table->string('schema_type', 100)->nullable()->after('og_image_id');
        });
    }

    public function down(): void
    {
        Schema::table('seo', function (Blueprint $table) {
            $table->dropConstrainedForeignId('og_image_id');
            $table->dropColumn([
                'focus_keyword',
                'og_title',
                'og_description',
                'schema_type',
            ]);
        });
    }
};
