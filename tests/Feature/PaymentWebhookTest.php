<?php

namespace Tests\Feature;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Models\AccessPackage;
use App\Models\Branch;
use App\Models\RevenueShareRule;
use App\Models\Tenant;
use App\Models\Transaction;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_palmpesa_webhook_fulfills_successful_transaction_idempotently(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $tenant = Tenant::query()->where('slug', 'coastfi-networks')->firstOrFail();
        $branch = Branch::query()->where('tenant_id', $tenant->id)->where('code', 'KRK')->firstOrFail();
        $package = AccessPackage::query()->where('tenant_id', $tenant->id)->where('name', 'Coast Quick Hour')->firstOrFail();
        $rule = RevenueShareRule::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $transaction = Transaction::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'access_package_id' => $package->id,
            'revenue_share_rule_id' => $rule->id,
            'source' => TransactionSource::MobileMoney,
            'status' => TransactionStatus::Pending,
            'reference' => 'PRT-WEBHOOK-100',
            'provider_reference' => 'SELCOM-ORDER-200',
            'phone_number' => '255712000999',
            'amount' => 1000,
            'gateway_fee' => 0,
            'currency' => 'TZS',
            'metadata' => [
                'channel' => 'portal',
                'device_ip_address' => '127.0.0.1',
            ],
        ]);

        $payload = [
            'reference' => $transaction->reference,
            'order_id' => 'SELCOM-ORDER-200',
            'status' => 'completed',
            'fee' => 75,
            'event' => 'payment.success',
        ];

        $this->postJson('/api/v1/webhooks/palmpesa', $payload)->assertOk();
        $this->postJson('/api/v1/webhooks/palmpesa', $payload)->assertOk();

        $transaction->refresh();

        $this->assertSame(TransactionStatus::Successful, $transaction->status);

        $this->assertDatabaseHas('revenue_allocations', [
            'transaction_id' => $transaction->id,
        ]);
        $this->assertSame(1, $transaction->callbacks()->count());
        $this->assertSame(1, $transaction->hotspotSessions()->count());
        $this->assertSame(1, $transaction->ledgerEntries()->where('entry_type', 'sale')->count());
    }
}
