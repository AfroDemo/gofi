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
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OperationsPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_operations_pages(): void
    {
        $this->get('/packages')->assertRedirect('/login');
        $this->get('/vouchers')->assertRedirect('/login');
        $this->get('/transactions')->assertRedirect('/login');
    }

    public function test_platform_admin_sees_all_packages_across_tenants(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/packages')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/packages', false)
                ->where('viewer.scope', 'platform')
                ->where('summary.total', 3)
                ->where('summary.active', 3)
                ->where('summary.voucher_profiles', 2)
                ->has('packages', 3)
                ->where('packages.0.name', 'Coast Quick Hour')
            );
    }

    public function test_tenant_user_sees_only_their_vouchers_and_transactions(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();

        $this->actingAs($owner)
            ->get('/vouchers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/vouchers', false)
                ->where('viewer.scope', 'tenant')
                ->where('summary.total', 3)
                ->where('summary.unused', 2)
                ->where('summary.used', 1)
                ->has('vouchers', 3)
                ->where('vouchers.0.tenant', 'CoastFi Networks')
            );

        $this->actingAs($owner)
            ->get('/transactions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/transactions', false)
                ->where('viewer.scope', 'tenant')
                ->where('summary.gross_successful', 2000)
                ->where('summary.pending_count', 1)
                ->where('summary.failed_count', 0)
                ->has('transactions', 3)
                ->where('transactions.0.tenant', 'CoastFi Networks')
            );
    }

    public function test_operations_pages_apply_filters_without_breaking_tenant_scope(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/packages?type=mixed')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/packages', false)
                ->where('filters.type', 'mixed')
                ->where('summary.total', 2)
                ->has('packages', 2)
            );

        $this->actingAs($owner)
            ->get('/vouchers?status=unused')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/vouchers', false)
                ->where('filters.status', 'unused')
                ->where('summary.total', 2)
                ->where('summary.unused', 2)
                ->has('vouchers', 2)
                ->where('vouchers.0.tenant', 'CoastFi Networks')
            );

        $this->actingAs($owner)
            ->get('/transactions?status=pending')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/transactions', false)
                ->where('filters.status', 'pending')
                ->where('summary.pending_count', 1)
                ->has('transactions', 1)
                ->where('transactions.0.reference', 'TXN-1003')
            );
    }

    public function test_transactions_page_can_filter_attention_watchlist(): void
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
            'reference' => 'TXN-WATCH-100',
            'provider_reference' => 'SELCOM-WATCH-100',
            'phone_number' => '255712000666',
            'amount' => 1000,
            'gateway_fee' => 0,
            'currency' => 'TZS',
        ]);

        $transaction->timestamps = false;
        $transaction->forceFill([
            'created_at' => Carbon::now()->subMinutes(8),
            'updated_at' => Carbon::now()->subMinutes(8),
        ])->save();

        $this->actingAs($admin)
            ->get('/transactions?attention=review')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/transactions', false)
                ->where('filters.attention', 'review')
                ->where('summary.needs_review_count', 2)
                ->where('summary.stale_pending_count', 1)
                ->has('transactions', 2)
            );

        $this->actingAs($admin)
            ->get('/transactions?attention=stale_pending')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/transactions', false)
                ->where('filters.attention', 'stale_pending')
                ->where('summary.needs_review_count', 1)
                ->where('summary.stale_pending_count', 1)
                ->has('transactions', 1)
                ->where('transactions.0.reference', 'TXN-WATCH-100')
                ->where('transactions.0.attention_level', 'stale_pending')
            );
    }
}
