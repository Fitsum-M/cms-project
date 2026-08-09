<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_types', function (Blueprint $table) {
            $table->boolean('supports_categories')->default(true)->after('icon');
            $table->boolean('supports_tags')->default(true)->after('supports_categories');
        });
    }

    public function down(): void
    {
        Schema::table('post_types', function (Blueprint $table) {
            $table->dropColumn(['supports_categories', 'supports_tags']);
        });
    }
};
