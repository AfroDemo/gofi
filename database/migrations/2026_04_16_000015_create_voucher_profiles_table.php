<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('access_package_id')->nullable()->constrained('access_packages')->nullOnDelete();
            $table->string('name');
            $table->string('code_prefix', 12)->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedBigInteger('data_limit_mb')->nullable();
            $table->unsignedInteger('speed_limit_kbps')->nullable();
            $table->unsignedInteger('expires_in_days')->nullable();
            $table->boolean('mac_lock_on_first_use')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_profiles');
    }
};
