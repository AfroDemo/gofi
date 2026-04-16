<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operator_follow_ups', function (Blueprint $table) {
            $table->foreignId('acknowledged_by_user_id')->nullable()->after('resolved_by_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable()->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('operator_follow_ups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acknowledged_by_user_id');
            $table->dropColumn('acknowledged_at');
        });
    }
};
