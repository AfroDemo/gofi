<?php

use App\Enums\LedgerDirection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payout_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction')->default(LedgerDirection::Credit->value);
            $table->string('entry_type');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('TZS');
            $table->decimal('balance_after', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('posted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
