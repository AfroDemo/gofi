<?php

namespace Tests\Feature;

use App\Enums\HotspotSessionStatus;
use App\Models\HotspotSession;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTerminationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_terminate_active_session_with_reason(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $session = HotspotSession::query()->where('device_mac_address', 'AA:BB:CC:DD:EE:10')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('sessions.terminate', $session), [
                'termination_reason' => 'Customer requested early disconnection after switching devices.',
            ])
            ->assertRedirect(route('sessions.show', $session));

        $session->refresh();

        $this->assertSame(HotspotSessionStatus::Terminated, $session->status);
        $this->assertNotNull($session->ended_at);
        $this->assertSame('Customer requested early disconnection after switching devices.', $session->termination_reason);
        $this->assertSame($admin->id, $session->terminated_by_user_id);
    }

    public function test_tenant_user_cannot_terminate_other_tenant_session(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenantSession = HotspotSession::query()->where('device_mac_address', 'AA:BB:CC:DD:EE:20')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('sessions.terminate', $otherTenantSession), [
                'termination_reason' => 'Unauthorized attempt',
            ])
            ->assertNotFound();
    }

    public function test_expired_session_cannot_be_terminated_again(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $session = HotspotSession::query()->where('device_mac_address', 'AA:BB:CC:DD:EE:01')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('sessions.show', $session))
            ->post(route('sessions.terminate', $session), [
                'termination_reason' => 'Should not be allowed',
            ])
            ->assertRedirect(route('sessions.show', $session));

        $session->refresh();

        $this->assertSame(HotspotSessionStatus::Expired, $session->status);
        $this->assertNull($session->termination_reason);
        $this->assertNull($session->terminated_by_user_id);
    }
}
