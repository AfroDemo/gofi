<?php

namespace Tests\Feature;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Models\AccessPackage;
use App\Models\Branch;
use App\Models\RevenueShareRule;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_sees_global_operational_metrics(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard', false)
                ->where('viewer.scope', 'platform')
                ->where('viewer.name', 'Platform overview')
                ->where('summary.revenue', 4500)
                ->where('summary.tenants', 2)
                ->where('summary.branches', 3)
                ->where('summary.escalations', 2)
                ->where('escalations.summary.total', 2)
                ->where('escalations.summary.unavailable_branches', 1)
                ->where('escalations.summary.open_incidents', 1)
                ->where('escalations.summary.payment_followups', 0)
                ->where('myFollowUps.summary.total', 0)
                ->where('myFollowUps.summary.awaiting_acknowledgement', 0)
                ->where('myFollowUps.summary.acknowledged', 0)
                ->has('escalations.items', 2)
                ->has('myFollowUps.items', 0)
            );
    }

    public function test_tenant_user_sees_only_their_tenant_dashboard_metrics(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard', false)
                ->where('viewer.scope', 'tenant')
                ->where('viewer.name', 'CoastFi Networks')
                ->where('summary.revenue', 2000)
                ->where('summary.tenants', 1)
                ->where('summary.branches', 2)
                ->where('summary.escalations', 2)
                ->where('escalations.summary.total', 2)
                ->where('escalations.summary.unavailable_branches', 1)
                ->where('escalations.summary.open_incidents', 1)
                ->where('escalations.summary.payment_followups', 0)
                ->where('myFollowUps.summary.total', 1)
                ->where('myFollowUps.summary.awaiting_acknowledgement', 1)
                ->where('myFollowUps.summary.acknowledged', 0)
                ->where('myFollowUps.summary.transactions', 1)
                ->where('myFollowUps.summary.branches', 0)
                ->where('myFollowUps.summary.devices', 0)
                ->has('escalations.items', 2)
                ->has('myFollowUps.items', 1)
                ->where('myFollowUps.items.0.type', 'transaction')
                ->where('myFollowUps.items.0.title', 'TXN-1003')
            );
    }

    public function test_tenant_dashboard_hides_other_tenant_escalations(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'neema@citywave.test')->firstOrFail();

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard', false)
                ->where('viewer.scope', 'tenant')
                ->where('viewer.name', 'CityWave Hotspots')
                ->where('summary.escalations', 0)
                ->where('escalations.summary.total', 0)
                ->where('escalations.summary.unavailable_branches', 0)
                ->where('escalations.summary.open_incidents', 0)
                ->where('escalations.summary.payment_followups', 0)
                ->where('myFollowUps.summary.total', 0)
                ->where('myFollowUps.summary.awaiting_acknowledgement', 0)
                ->where('myFollowUps.summary.acknowledged', 0)
                ->has('escalations.items', 0)
                ->has('myFollowUps.items', 0)
            );
    }

    public function test_dashboard_includes_payment_follow_up_escalations_when_access_was_not_fulfilled(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $branch = Branch::query()->where('code', 'MWG')->firstOrFail();
        $package = AccessPackage::query()->where('branch_id', $branch->id)->firstOrFail();
        $rule = RevenueShareRule::query()->where('tenant_id', $branch->tenant_id)->firstOrFail();

        $stalePending = Transaction::query()->create([
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
            'access_package_id' => $package->id,
            'revenue_share_rule_id' => $rule->id,
            'initiated_by_user_id' => $owner->id,
            'source' => TransactionSource::MobileMoney,
            'status' => TransactionStatus::Pending,
            'reference' => 'TXN-3001',
            'provider_reference' => 'PP-3001',
            'phone_number' => '255713000111',
            'amount' => 3000,
            'gateway_fee' => 0,
            'currency' => 'TZS',
            'metadata' => [
                'payment' => [
                    'gateway' => 'palmpesa',
                ],
            ],
        ]);
        $stalePending->forceFill([
            'created_at' => now()->subMinutes(11),
            'updated_at' => now()->subMinutes(11),
        ])->saveQuietly();

        Transaction::query()->create([
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
            'access_package_id' => $package->id,
            'revenue_share_rule_id' => $rule->id,
            'initiated_by_user_id' => $owner->id,
            'source' => TransactionSource::MobileMoney,
            'status' => TransactionStatus::Successful,
            'reference' => 'TXN-3002',
            'provider_reference' => 'PP-3002',
            'phone_number' => '255713000222',
            'amount' => 3000,
            'gateway_fee' => 120,
            'currency' => 'TZS',
            'confirmed_at' => now()->subMinutes(7),
            'metadata' => [
                'payment' => [
                    'gateway' => 'palmpesa',
                    'branch_unavailable_at_confirmation' => true,
                    'branch_status' => 'maintenance',
                ],
            ],
        ]);

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard', false)
                ->where('summary.escalations', 4)
                ->where('escalations.summary.total', 4)
                ->where('escalations.summary.unavailable_branches', 1)
                ->where('escalations.summary.open_incidents', 1)
                ->where('escalations.summary.payment_followups', 2)
                ->has('escalations.items', 4)
            );
    }

    public function test_operator_dashboard_rolls_up_items_assigned_to_them(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $operator = User::query()->where('email', 'moses@coastfi.test')->firstOrFail();

        $this->actingAs($operator)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard', false)
                ->where('viewer.scope', 'tenant')
                ->where('myFollowUps.summary.total', 2)
                ->where('myFollowUps.summary.branches', 1)
                ->where('myFollowUps.summary.devices', 1)
                ->where('myFollowUps.summary.transactions', 0)
                ->where('myFollowUps.summary.awaiting_acknowledgement', 1)
                ->where('myFollowUps.summary.acknowledged', 1)
                ->has('myFollowUps.items', 2)
                ->where('myFollowUps.items.0.type', 'device')
                ->where('myFollowUps.items.0.title', 'MWG RTR 01')
                ->where('myFollowUps.items.0.acknowledged_at', fn ($value) => filled($value))
                ->where('myFollowUps.items.1.type', 'branch')
                ->where('myFollowUps.items.1.title', 'Mwenge Corner')
                ->where('myFollowUps.items.1.acknowledged_at', null)
            );
    }
}
