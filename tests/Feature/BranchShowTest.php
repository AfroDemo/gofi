<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BranchShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_branch_detail(): void
    {
        $this->get('/branches/1')->assertRedirect('/login');
    }

    public function test_platform_admin_can_view_branch_detail_with_status_history(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $branch = Branch::query()->where('code', 'MWG')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('branches.show', $branch))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/branch-show', false)
                ->where('branch.name', 'Mwenge Corner')
                ->where('summary.open_incidents', 1)
                ->has('status_history', 1)
                ->where('status_history.0.to_status', 'maintenance')
                ->has('recent_incidents', 1)
            );
    }

    public function test_tenant_user_cannot_view_other_tenant_branch_detail(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenantBranch = Branch::query()->where('code', 'MLM')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('branches.show', $otherTenantBranch))
            ->assertNotFound();
    }
}
