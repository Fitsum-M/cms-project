<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('post_type')->index();
            $table->softDeletes();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('slug')->index();
            $table->timestamp('published_at')->nullable()->after('status')->index();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropSoftDeletes();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['status', 'published_at']);
            $table->dropSoftDeletes();
        });
    }
};
