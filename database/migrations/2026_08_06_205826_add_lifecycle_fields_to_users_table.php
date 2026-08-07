<?php

use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default(UserStatus::PendingActivation->value)->after('bio');
            $table->string('invitation_token', 64)->nullable()->unique()->after('status');
            $table->timestamp('invitation_sent_at')->nullable()->after('invitation_token');
            $table->foreignId('invited_by')->nullable()->after('invitation_sent_at')->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable()->after('invited_by');
            $table->timestamp('suspended_at')->nullable()->after('activated_at');
            $table->softDeletes();
        });

        DB::table('users')->update([
            'status' => UserStatus::Active->value,
            'activated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by');
            $table->dropSoftDeletes();
            $table->dropColumn([
                'status',
                'invitation_token',
                'invitation_sent_at',
                'activated_at',
                'suspended_at',
            ]);
        });
    }
};
