<?php

namespace Tests\Feature;

use App\Models\HotspotDevice;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DeviceShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_device_detail(): void
    {
        $this->get('/devices/1')->assertRedirect('/login');
    }

    public function test_platform_admin_can_view_device_detail_with_branch_context(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $device = HotspotDevice::query()->where('identifier', 'KRK-RTR-01')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('devices.show', $device))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/device-show', false)
                ->where('device.identifier', 'KRK-RTR-01')
                ->where('device.status', 'online')
                ->where('device.branch.name', 'Kariakoo Hub')
                ->where('branch_overview.device_count', 1)
                ->where('branch_overview.active_sessions', 1)
                ->has('recent_sessions', 2)
                ->has('recent_transactions', 2)
            );
    }

    public function test_tenant_user_cannot_view_other_tenant_device_detail(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenantDevice = HotspotDevice::query()->where('identifier', 'MLM-RTR-01')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('devices.show', $otherTenantDevice))
            ->assertNotFound();
    }
}
