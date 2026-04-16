<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherProfile;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_create_voucher_profile_for_their_workspace(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $tenant = Tenant::query()->where('name', 'CoastFi Networks')->firstOrFail();
        $package = $tenant->packages()->where('name', 'Coast Quick Hour')->firstOrFail();
        $branchId = $tenant->branches()->value('id');

        $this->actingAs($owner)
            ->post(route('voucher-profiles.store'), [
                'tenant_id' => $tenant->id,
                'branch_id' => $branchId,
                'access_package_id' => $package->id,
                'name' => 'Desk Counter Cards',
                'code_prefix' => 'DCC',
                'price' => 1200,
                'duration_minutes' => 90,
                'speed_limit_kbps' => 4096,
                'expires_in_days' => 21,
                'mac_lock_on_first_use' => true,
                'is_active' => true,
            ])
            ->assertRedirect(route('vouchers.index'));

        $this->assertDatabaseHas('voucher_profiles', [
            'tenant_id' => $tenant->id,
            'name' => 'Desk Counter Cards',
            'code_prefix' => 'DCC',
        ]);
    }

    public function test_tenant_user_cannot_create_voucher_profile_for_another_tenant(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenant = Tenant::query()->where('name', 'CityWave Hotspots')->firstOrFail();
        $otherPackage = $otherTenant->packages()->firstOrFail();

        $this->actingAs($owner)
            ->from(route('voucher-profiles.create'))
            ->post(route('voucher-profiles.store'), [
                'tenant_id' => $otherTenant->id,
                'access_package_id' => $otherPackage->id,
                'name' => 'Cross Tenant Profile',
                'code_prefix' => 'XTP',
            ])
            ->assertRedirect(route('voucher-profiles.create'))
            ->assertSessionHasErrors('tenant_id');

        $this->assertDatabaseMissing('voucher_profiles', [
            'name' => 'Cross Tenant Profile',
        ]);
    }

    public function test_platform_admin_can_update_existing_voucher_profile(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $profile = VoucherProfile::query()->where('name', 'City Flex Voucher')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('voucher-profiles.update', $profile), [
                'tenant_id' => $profile->tenant_id,
                'branch_id' => $profile->branch_id,
                'access_package_id' => $profile->access_package_id,
                'name' => 'City Flex Express Voucher',
                'code_prefix' => 'CFX',
                'price' => 2800,
                'duration_minutes' => 120,
                'data_limit_mb' => 2500,
                'speed_limit_kbps' => 5120,
                'expires_in_days' => 10,
                'mac_lock_on_first_use' => true,
                'is_active' => true,
            ])
            ->assertRedirect(route('vouchers.index'));

        $this->assertDatabaseHas('voucher_profiles', [
            'id' => $profile->id,
            'name' => 'City Flex Express Voucher',
            'code_prefix' => 'CFX',
        ]);
    }

    public function test_tenant_user_can_generate_voucher_batch_for_their_profile(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $profile = VoucherProfile::query()->where('name', 'Quick Hour Scratch Card')->firstOrFail();
        $before = Voucher::query()->where('voucher_profile_id', $profile->id)->count();

        $this->actingAs($owner)
            ->post(route('voucher-batches.store', $profile), [
                'quantity' => 5,
            ])
            ->assertRedirect(route('vouchers.index'));

        $this->assertSame($before + 5, Voucher::query()->where('voucher_profile_id', $profile->id)->count());
        $this->assertDatabaseHas('vouchers', [
            'voucher_profile_id' => $profile->id,
            'created_by_user_id' => $owner->id,
        ]);
    }

    public function test_tenant_user_cannot_generate_vouchers_for_other_tenant_profile(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherProfile = VoucherProfile::query()->where('name', 'City Flex Voucher')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('voucher-batches.store', $otherProfile), [
                'quantity' => 2,
            ])
            ->assertNotFound();
    }
}
