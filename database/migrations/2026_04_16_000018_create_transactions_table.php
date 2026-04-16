<?php

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('access_package_id')->nullable()->constrained('access_packages')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('revenue_share_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->default(TransactionSource::MobileMoney->value);
            $table->string('status')->default(TransactionStatus::Pending->value);
            $table->string('reference')->unique();
            $table->string('provider_reference')->nullable()->index();
            $table->string('phone_number', 32)->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('gateway_fee', 12, 2)->default(0);
            $table->string('currency', 3)->default('TZS');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
