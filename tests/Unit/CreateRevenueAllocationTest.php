<?php

namespace Tests\Unit;

use App\Actions\Finance\CreateRevenueAllocation;
use App\Enums\PlatformRole;
use App\Enums\RevenueShareModel;
use App\Enums\TenantStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Models\RevenueShareRule;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateRevenueAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_hybrid_revenue_shares_from_the_transaction_snapshot(): void
    {
        $user = User::factory()->create([
            'platform_role' => PlatformRole::TenantUser,
        ]);

        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => TenantStatus::Active,
            'currency' => 'TZS',
            'timezone' => 'Africa/Dar_es_Salaam',
            'owner_user_id' => $user->id,
        ]);

        $rule = RevenueShareRule::create([
            'tenant_id' => $tenant->id,
            'name' => 'Hybrid default',
            'model' => RevenueShareModel::Hybrid,
            'platform_percentage' => 10,
            'platform_fixed_fee' => 500,
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'tenant_id' => $tenant->id,
            'revenue_share_rule_id' => $rule->id,
            'initiated_by_user_id' => $user->id,
            'source' => TransactionSource::MobileMoney,
            'status' => TransactionStatus::Successful,
            'reference' => 'TXN-UNIT-0001',
            'provider_reference' => 'TXN-UNIT-0001',
            'amount' => 2500,
            'gateway_fee' => 100,
            'currency' => 'TZS',
        ]);

        $allocation = app(CreateRevenueAllocation::class)->execute($transaction, $rule);

        $this->assertSame('740.00', $allocation->platform_amount);
        $this->assertSame('1660.00', $allocation->tenant_amount);
        $this->assertSame($rule->id, $allocation->snapshot['rule_id']);
    }
}
