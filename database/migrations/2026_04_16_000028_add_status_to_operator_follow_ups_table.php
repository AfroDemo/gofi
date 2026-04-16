<?php

use App\Enums\OperatorFollowUpStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operator_follow_ups', function (Blueprint $table) {
            $table->string('status')->default(OperatorFollowUpStatus::NeedsFollowUp->value)->after('assigned_at');
            $table->foreignId('resolved_by_user_id')->nullable()->after('assigned_by_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('operator_follow_ups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_by_user_id');
            $table->dropColumn(['status', 'resolved_at']);
        });
    }
};
