<?php

namespace Tests\Feature;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Models\AccessPackage;
use App\Models\Branch;
use App\Models\RevenueShareRule;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransactionShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_transaction_detail(): void
    {
        $this->get('/transactions/1')->assertRedirect('/login');
    }

    public function test_platform_admin_can_view_transaction_detail_with_callbacks_and_allocation(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $transaction = Transaction::query()->where('reference', 'TXN-1001')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('transactions.show', $transaction))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/transaction-show', false)
                ->where('transaction.reference', 'TXN-1001')
                ->where('transaction.status', 'successful')
                ->where('transaction.allocation.platform_amount', 169.2)
                ->where('transaction.payment.provider_reference', 'TXN-1001')
                ->has('transaction.callbacks', 1)
                ->has('transaction.sessions', 1)
                ->has('transaction.ledger_entries', 1)
            );
    }

    public function test_platform_admin_can_refresh_pending_transaction_from_detail_view(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
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
            'reference' => 'TXN-OPS-REFRESH-100',
            'provider_reference' => 'SELCOM-OPS-100',
            'phone_number' => '255712000321',
            'amount' => 1000,
            'gateway_fee' => 0,
            'currency' => 'TZS',
            'metadata' => [
                'payment' => [
                    'gateway' => 'palmpesa',
                ],
            ],
        ]);

        config([
            'payment.gateway' => 'palmpesa',
            'payment.fallback_gateway' => 'snippe',
            'payment.palmpesa.enabled' => true,
            'payment.snippe.enabled' => false,
        ]);

        Http::fake([
            'https://palmpesa.drmlelwa.co.tz/api/order-status' => Http::response([
                'status' => 'completed',
                'order_id' => 'SELCOM-OPS-100',
                'fee' => 55,
                'paid_at' => now()->toIso8601String(),
            ], 200),
        ]);

        $this->actingAs($admin)
            ->post(route('transactions.refresh-status', $transaction))
            ->assertRedirect(route('transactions.show', $transaction));

        $transaction->refresh();

        $this->assertSame(TransactionStatus::Successful, $transaction->status);
        $this->assertSame(1, $transaction->hotspotSessions()->count());
        $this->assertSame(1, $transaction->ledgerEntries()->where('entry_type', 'sale')->count());
    }

    public function test_tenant_user_cannot_view_other_tenant_transaction_detail(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenantTransaction = Transaction::query()->where('reference', 'TXN-2001')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('transactions.show', $otherTenantTransaction))
            ->assertNotFound();
    }

    public function test_tenant_user_cannot_refresh_other_tenant_transaction(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenantTransaction = Transaction::query()->where('reference', 'TXN-2001')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('transactions.refresh-status', $otherTenantTransaction))
            ->assertNotFound();
    }
}
