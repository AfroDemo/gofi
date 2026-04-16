<?php

use App\Enums\HotspotSessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotspot_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('access_package_id')->nullable()->constrained('access_packages')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('authorized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_mac_address', 32);
            $table->string('device_ip_address', 45)->nullable();
            $table->string('status')->default(HotspotSessionStatus::Pending->value);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedBigInteger('data_limit_mb')->nullable();
            $table->unsignedBigInteger('data_used_mb')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspot_sessions');
    }
};
