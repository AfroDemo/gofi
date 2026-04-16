<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('model');
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('gateway_fee', 12, 2)->default(0);
            $table->decimal('platform_amount', 12, 2);
            $table->decimal('tenant_amount', 12, 2);
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_allocations');
    }
};
