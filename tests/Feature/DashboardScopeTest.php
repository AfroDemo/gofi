<?php

namespace Tests\Feature;

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
            );
    }
}
