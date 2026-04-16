<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\HotspotDevice;
use App\Models\OperatorNote;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OperatorNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_add_transaction_follow_up_note(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $transaction = Transaction::query()->where('reference', 'TXN-1003')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('transactions.notes.store', $transaction), [
                'note' => 'Picked up this payment follow-up and will compare callback logs against provider status.',
            ])
            ->assertRedirect(route('transactions.show', $transaction));

        $this->assertDatabaseHas('operator_notes', [
            'tenant_id' => $transaction->tenant_id,
            'branch_id' => $transaction->branch_id,
            'user_id' => $admin->id,
            'noteable_type' => Transaction::class,
            'noteable_id' => $transaction->id,
        ]);
    }

    public function test_tenant_user_can_add_branch_follow_up_note_and_see_it_on_detail(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $branch = Branch::query()->where('code', 'MWG')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('branches.notes.store', $branch), [
                'note' => 'Branch attendant confirmed the site should stay in maintenance until replacement power is installed.',
            ])
            ->assertRedirect(route('branches.show', $branch));

        $this->actingAs($owner)
            ->get(route('branches.show', $branch))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/branch-show', false)
                ->has('notes', 2)
                ->where('notes.0.note', 'Branch attendant confirmed the site should stay in maintenance until replacement power is installed.')
                ->where('notes.0.author.name', 'Amina Juma')
            );
    }

    public function test_tenant_user_can_add_device_follow_up_note(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $device = HotspotDevice::query()->where('identifier', 'MWG-RTR-01')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('devices.notes.store', $device), [
                'note' => 'Device escalation acknowledged. Waiting for onsite power team before attempting another restart.',
            ])
            ->assertRedirect(route('devices.show', $device));

        $this->assertDatabaseHas('operator_notes', [
            'tenant_id' => $device->tenant_id,
            'branch_id' => $device->branch_id,
            'user_id' => $owner->id,
            'noteable_type' => HotspotDevice::class,
            'noteable_id' => $device->id,
        ]);
    }

    public function test_tenant_user_cannot_add_note_to_other_tenant_transaction(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenantTransaction = Transaction::query()->where('reference', 'TXN-2001')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('transactions.notes.store', $otherTenantTransaction), [
                'note' => 'Unauthorized note attempt.',
            ])
            ->assertNotFound();

        $this->assertSame(0, OperatorNote::query()->where('noteable_type', Transaction::class)->where('noteable_id', $otherTenantTransaction->id)->count());
    }
}
