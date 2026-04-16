<?php

namespace Tests\Feature;

use App\Models\HotspotSession;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SessionShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_session_detail(): void
    {
        $this->get('/sessions/1')->assertRedirect('/login');
    }

    public function test_platform_admin_can_view_session_detail_with_fulfilment_evidence(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $session = HotspotSession::query()->where('device_mac_address', 'AA:BB:CC:DD:EE:01')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('sessions.show', $session))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/session-show', false)
                ->where('session.status', 'expired')
                ->where('session.branch.name', 'Kariakoo Hub')
                ->where('session.package.name', 'Coast Quick Hour')
                ->where('session.voucher.code', 'CFH-1001')
                ->where('session.transaction.reference', 'TXN-1002')
                ->where('session.authorizer.name', 'Moses Ally')
            );
    }

    public function test_tenant_user_cannot_view_other_tenant_session_detail(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenantSession = HotspotSession::query()->where('device_mac_address', 'AA:BB:CC:DD:EE:20')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('sessions.show', $otherTenantSession))
            ->assertNotFound();
    }
}
