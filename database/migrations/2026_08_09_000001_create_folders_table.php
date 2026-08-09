<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->timestamps();

            $table->index(['parent_id', 'name']);
        });

        Schema::table('media_assets', function (Blueprint $table) {
            $table->foreign('folder_id')
                ->references('id')
                ->on('folders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
        });

        Schema::dropIfExists('folders');
    }
};
