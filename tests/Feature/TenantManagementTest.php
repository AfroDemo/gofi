<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_create_tenant(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $owner = User::factory()->create([
            'platform_role' => PlatformRole::TenantUser,
        ]);

        $this->actingAs($admin)
            ->post(route('tenants.store'), [
                'name' => 'LakeNet Wireless',
                'slug' => 'lakenet-wireless',
                'status' => 'active',
                'currency' => 'TZS',
                'country_code' => 'TZ',
                'timezone' => 'Africa/Dar_es_Salaam',
                'owner_user_id' => $owner->id,
            ])
            ->assertRedirect(route('tenants.index'));

        $this->assertDatabaseHas('tenants', [
            'name' => 'LakeNet Wireless',
            'slug' => 'lakenet-wireless',
            'owner_user_id' => $owner->id,
        ]);
    }

    public function test_tenant_user_cannot_create_tenant(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('tenants.store'), [
                'name' => 'Blocked Tenant',
                'slug' => 'blocked-tenant',
                'status' => 'active',
                'currency' => 'TZS',
                'timezone' => 'Africa/Dar_es_Salaam',
            ])
            ->assertForbidden();
    }

    public function test_tenant_user_can_update_their_scoped_tenant(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $tenant = Tenant::query()->where('name', 'CoastFi Networks')->firstOrFail();

        $this->actingAs($owner)
            ->patch(route('tenants.update', $tenant), [
                'name' => 'CoastFi Networks Plus',
                'currency' => 'TZS',
                'country_code' => 'TZ',
                'timezone' => 'Africa/Dar_es_Salaam',
            ])
            ->assertRedirect(route('tenants.index'));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'CoastFi Networks Plus',
        ]);
    }
}
