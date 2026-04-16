<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_create_branch_in_their_workspace(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $tenant = Tenant::query()->where('name', 'CoastFi Networks')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('branches.store'), [
                'tenant_id' => $tenant->id,
                'name' => 'Mbezi Point',
                'code' => 'MBZ',
                'status' => 'active',
                'location' => 'Dar es Salaam',
                'address' => 'Mbezi Beach',
                'manager_user_id' => $owner->id,
            ])
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('branches', [
            'tenant_id' => $tenant->id,
            'name' => 'Mbezi Point',
            'code' => 'MBZ',
        ]);
    }

    public function test_tenant_user_cannot_create_branch_for_other_tenant(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenant = Tenant::query()->where('name', 'CityWave Hotspots')->firstOrFail();

        $this->actingAs($owner)
            ->from(route('branches.create'))
            ->post(route('branches.store'), [
                'tenant_id' => $otherTenant->id,
                'name' => 'Cross Tenant Branch',
                'code' => 'XTB',
                'status' => 'active',
            ])
            ->assertRedirect(route('branches.create'))
            ->assertSessionHasErrors('tenant_id');

        $this->assertDatabaseMissing('branches', [
            'name' => 'Cross Tenant Branch',
        ]);
    }

    public function test_platform_admin_can_update_existing_branch(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $branch = Branch::query()->where('name', 'Mlimani Point')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('branches.update', $branch), [
                'tenant_id' => $branch->tenant_id,
                'name' => 'Mlimani Point Central',
                'code' => 'MLM',
                'status' => 'maintenance',
                'location' => 'Mwanza',
                'address' => 'Capri Point, Mwanza',
                'manager_user_id' => $branch->manager_user_id,
            ])
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Mlimani Point Central',
            'status' => 'maintenance',
        ]);
    }
}
