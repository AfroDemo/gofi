<?php

namespace Tests\Feature;

use App\Enums\PackageType;
use App\Models\AccessPackage;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_create_package_for_their_workspace(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $tenant = Tenant::query()->where('name', 'CoastFi Networks')->firstOrFail();
        $branchId = $tenant->branches()->value('id');

        $this->actingAs($owner)
            ->post(route('packages.store'), [
                'tenant_id' => $tenant->id,
                'branch_id' => $branchId,
                'name' => 'Sunrise 3 Hours',
                'package_type' => PackageType::Mixed->value,
                'description' => 'Early day browsing bundle',
                'price' => 3500,
                'duration_minutes' => 180,
                'data_limit_mb' => 3000,
                'speed_limit_kbps' => 6144,
                'is_active' => true,
            ])
            ->assertRedirect(route('packages.index'));

        $this->assertDatabaseHas('access_packages', [
            'tenant_id' => $tenant->id,
            'name' => 'Sunrise 3 Hours',
            'package_type' => PackageType::Mixed->value,
            'currency' => 'TZS',
        ]);
    }

    public function test_tenant_user_cannot_create_package_for_another_tenant(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenant = Tenant::query()->where('name', 'CityWave Hotspots')->firstOrFail();

        $this->actingAs($owner)
            ->from(route('packages.create'))
            ->post(route('packages.store'), [
                'tenant_id' => $otherTenant->id,
                'name' => 'Cross Tenant Package',
                'package_type' => PackageType::Time->value,
                'price' => 1000,
                'duration_minutes' => 60,
                'is_active' => true,
            ])
            ->assertRedirect(route('packages.create'))
            ->assertSessionHasErrors('tenant_id');

        $this->assertDatabaseMissing('access_packages', [
            'name' => 'Cross Tenant Package',
        ]);
    }

    public function test_platform_admin_can_update_existing_package(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $package = AccessPackage::query()->where('name', 'City Flex 2 Hours')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('packages.update', $package), [
                'tenant_id' => $package->tenant_id,
                'branch_id' => $package->branch_id,
                'name' => 'City Flex 4 Hours',
                'package_type' => PackageType::Mixed->value,
                'description' => 'Updated commuter bundle',
                'price' => 4200,
                'duration_minutes' => 240,
                'data_limit_mb' => 3500,
                'speed_limit_kbps' => 5120,
                'is_active' => true,
            ])
            ->assertRedirect(route('packages.index'));

        $this->assertDatabaseHas('access_packages', [
            'id' => $package->id,
            'name' => 'City Flex 4 Hours',
            'price' => 4200,
        ]);
    }
}
