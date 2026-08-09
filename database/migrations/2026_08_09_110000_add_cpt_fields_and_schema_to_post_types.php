<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_types', function (Blueprint $table) {
            $table->boolean('supports_excerpt')->default(true)->after('supports_tags');
            $table->boolean('supports_featured_image')->default(true)->after('supports_excerpt');
            $table->string('default_schema_type', 100)->nullable()->after('supports_featured_image');
        });
    }

    public function down(): void
    {
        Schema::table('post_types', function (Blueprint $table) {
            $table->dropColumn([
                'supports_excerpt',
                'supports_featured_image',
                'default_schema_type',
            ]);
        });
    }
};
