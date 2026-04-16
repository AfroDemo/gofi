<?php

namespace Tests\Feature;

use App\Enums\DeviceIncidentStatus;
use App\Models\DeviceIncident;
use App\Models\HotspotDevice;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DeviceIncidentTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_log_device_incident(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $device = HotspotDevice::query()->where('identifier', 'KRK-RTR-01')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('devices.incidents.store', $device), [
                'title' => 'Captive portal timing out',
                'severity' => 'critical',
                'details' => 'Attendants report repeated timeout complaints during peak traffic.',
            ])
            ->assertRedirect(route('devices.show', $device));

        $incident = DeviceIncident::query()->where('hotspot_device_id', $device->id)->where('title', 'Captive portal timing out')->firstOrFail();

        $this->assertSame('critical', $incident->severity->value);
        $this->assertSame(DeviceIncidentStatus::Open, $incident->status);
        $this->assertSame($admin->id, $incident->reported_by_user_id);
    }

    public function test_platform_admin_can_resolve_open_device_incident(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $device = HotspotDevice::query()->where('identifier', 'MWG-RTR-01')->firstOrFail();
        $incident = DeviceIncident::query()->where('hotspot_device_id', $device->id)->where('status', DeviceIncidentStatus::Open)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('devices.incidents.resolve', [$device, $incident]), [
                'resolution_notes' => 'Router power adapter replaced and branch heartbeat restored.',
            ])
            ->assertRedirect(route('devices.show', $device));

        $incident->refresh();

        $this->assertSame(DeviceIncidentStatus::Resolved, $incident->status);
        $this->assertSame('Router power adapter replaced and branch heartbeat restored.', $incident->resolution_notes);
        $this->assertSame($admin->id, $incident->resolved_by_user_id);
    }

    public function test_tenant_user_cannot_modify_other_tenant_device_incidents(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenantDevice = HotspotDevice::query()->where('identifier', 'MLM-RTR-01')->firstOrFail();
        $otherTenantIncident = DeviceIncident::query()->where('hotspot_device_id', $otherTenantDevice->id)->first();

        $this->actingAs($owner)
            ->post(route('devices.incidents.store', $otherTenantDevice), [
                'title' => 'Unauthorized incident',
                'severity' => 'low',
                'details' => 'Should not be allowed.',
            ])
            ->assertNotFound();

        if ($otherTenantIncident) {
            $this->actingAs($owner)
                ->post(route('devices.incidents.resolve', [$otherTenantDevice, $otherTenantIncident]), [
                    'resolution_notes' => 'Unauthorized resolve',
                ])
                ->assertNotFound();
        } else {
            $this->assertNull($otherTenantIncident);
        }
    }

    public function test_device_detail_shows_incident_context(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $device = HotspotDevice::query()->where('identifier', 'MWG-RTR-01')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('devices.show', $device))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/device-show', false)
                ->where('branch_overview.open_incidents', 1)
                ->has('incidents', 1)
                ->where('incidents.0.title', 'Router heartbeat missing')
                ->where('incidents.0.status', 'open')
            );
    }
}
