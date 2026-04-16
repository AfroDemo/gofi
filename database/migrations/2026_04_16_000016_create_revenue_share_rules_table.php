<?php

use App\Enums\RevenueShareModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_share_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('access_package_id')->nullable()->constrained('access_packages')->nullOnDelete();
            $table->string('name');
            $table->string('model')->default(RevenueShareModel::Percentage->value);
            $table->decimal('platform_percentage', 5, 2)->default(0);
            $table->decimal('platform_fixed_fee', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_share_rules');
    }
};
