<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_update_branch_status_with_reason(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $branch = Branch::query()->where('code', 'KRK')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('branches.update-status', $branch), [
                'status' => 'maintenance',
                'reason' => 'Switching the branch into maintenance during uplink diagnostics.',
            ])
            ->assertRedirect(route('branches.show', $branch));

        $branch->refresh();

        $this->assertSame('maintenance', $branch->status->value);
        $this->assertDatabaseHas('branch_status_events', [
            'branch_id' => $branch->id,
            'from_status' => 'active',
            'to_status' => 'maintenance',
            'reason' => 'Switching the branch into maintenance during uplink diagnostics.',
            'changed_by_user_id' => $admin->id,
        ]);
    }

    public function test_same_branch_status_is_rejected(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $branch = Branch::query()->where('code', 'KRK')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('branches.update-status', $branch), [
                'status' => 'active',
                'reason' => 'No change should happen.',
            ])
            ->assertRedirect(route('branches.show', $branch));

        $this->assertDatabaseMissing('branch_status_events', [
            'branch_id' => $branch->id,
            'reason' => 'No change should happen.',
        ]);
    }

    public function test_tenant_user_cannot_update_other_tenant_branch_status(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenantBranch = Branch::query()->where('code', 'MLM')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('branches.update-status', $otherTenantBranch), [
                'status' => 'maintenance',
                'reason' => 'Unauthorized change.',
            ])
            ->assertNotFound();
    }
}
