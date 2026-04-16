<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\HotspotDevice;
use App\Models\OperatorFollowUp;
use App\Models\OperatorNote;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OperatorFollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_detail_shows_current_follow_up_owner(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $branch = Branch::query()->where('code', 'MWG')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('branches.show', $branch))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/branch-show', false)
                ->where('follow_up.assigned_user.name', 'Moses Ally')
                ->where('follow_up.owned_by_viewer', false)
            );
    }

    public function test_tenant_user_can_take_transaction_follow_up_ownership(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $operator = User::query()->where('email', 'moses@coastfi.test')->firstOrFail();
        $transaction = Transaction::query()->where('reference', 'TXN-1003')->firstOrFail();

        $this->actingAs($operator)
            ->post(route('transactions.follow-up.store', $transaction))
            ->assertRedirect(route('transactions.show', $transaction));

        $followUp = OperatorFollowUp::query()
            ->where('followable_type', Transaction::class)
            ->where('followable_id', $transaction->id)
            ->firstOrFail();

        $this->assertSame($operator->id, $followUp->assigned_user_id);
        $this->assertSame($operator->id, $followUp->assigned_by_user_id);

        $this->assertDatabaseHas('operator_notes', [
            'tenant_id' => $transaction->tenant_id,
            'branch_id' => $transaction->branch_id,
            'user_id' => $operator->id,
            'noteable_type' => Transaction::class,
            'noteable_id' => $transaction->id,
            'note' => 'Moses Ally took ownership of this follow-up.',
        ]);
    }

    public function test_tenant_user_can_reassign_branch_follow_up_to_another_tenant_operator(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $operator = User::query()->where('email', 'moses@coastfi.test')->firstOrFail();
        $branch = Branch::query()->where('code', 'MWG')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('branches.follow-up.store', $branch), [
                'assigned_user_id' => $operator->id,
            ])
            ->assertRedirect(route('branches.show', $branch));

        $followUp = OperatorFollowUp::query()
            ->where('followable_type', Branch::class)
            ->where('followable_id', $branch->id)
            ->firstOrFail();

        $this->assertSame($operator->id, $followUp->assigned_user_id);
        $this->assertSame($owner->id, $followUp->assigned_by_user_id);

        $this->assertDatabaseHas('operator_notes', [
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
            'user_id' => $owner->id,
            'noteable_type' => Branch::class,
            'noteable_id' => $branch->id,
            'note' => 'Amina Juma assigned this follow-up to Moses Ally.',
        ]);
    }

    public function test_tenant_user_can_release_device_follow_up_ownership(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $operator = User::query()->where('email', 'moses@coastfi.test')->firstOrFail();
        $device = HotspotDevice::query()->where('identifier', 'MWG-RTR-01')->firstOrFail();

        $this->actingAs($operator)
            ->delete(route('devices.follow-up.destroy', $device))
            ->assertRedirect(route('devices.show', $device));

        $this->assertDatabaseMissing('operator_follow_ups', [
            'followable_type' => HotspotDevice::class,
            'followable_id' => $device->id,
        ]);

        $this->assertDatabaseHas('operator_notes', [
            'tenant_id' => $device->tenant_id,
            'branch_id' => $device->branch_id,
            'user_id' => $operator->id,
            'noteable_type' => HotspotDevice::class,
            'noteable_id' => $device->id,
            'note' => 'Moses Ally released follow-up ownership.',
        ]);
    }

    public function test_assigned_operator_can_acknowledge_device_follow_up(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $operator = User::query()->where('email', 'moses@coastfi.test')->firstOrFail();
        $branch = Branch::query()->where('code', 'MWG')->firstOrFail();

        $this->actingAs($operator)
            ->post(route('branches.follow-up.acknowledge', $branch))
            ->assertRedirect(route('branches.show', $branch));

        $followUp = OperatorFollowUp::query()
            ->where('followable_type', Branch::class)
            ->where('followable_id', $branch->id)
            ->firstOrFail();

        $this->assertSame($operator->id, $followUp->acknowledged_by_user_id);
        $this->assertNotNull($followUp->acknowledged_at);

        $this->assertDatabaseHas('operator_notes', [
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
            'user_id' => $operator->id,
            'noteable_type' => Branch::class,
            'noteable_id' => $branch->id,
            'note' => 'Moses Ally acknowledged this follow-up and is actively working it.',
        ]);
    }

    public function test_tenant_user_can_mark_transaction_follow_up_resolved(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $transaction = Transaction::query()->where('reference', 'TXN-1003')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('transactions.follow-up.resolve', $transaction))
            ->assertRedirect(route('transactions.show', $transaction));

        $followUp = OperatorFollowUp::query()
            ->where('followable_type', Transaction::class)
            ->where('followable_id', $transaction->id)
            ->firstOrFail();

        $this->assertSame('resolved', $followUp->status->value);
        $this->assertSame($owner->id, $followUp->resolved_by_user_id);
        $this->assertNotNull($followUp->resolved_at);

        $this->assertDatabaseHas('operator_notes', [
            'tenant_id' => $transaction->tenant_id,
            'branch_id' => $transaction->branch_id,
            'user_id' => $owner->id,
            'noteable_type' => Transaction::class,
            'noteable_id' => $transaction->id,
            'note' => 'Amina Juma marked this follow-up as resolved.',
        ]);
    }

    public function test_tenant_user_can_reopen_resolved_branch_follow_up(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $branch = Branch::query()->where('code', 'MWG')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('branches.follow-up.resolve', $branch))
            ->assertRedirect(route('branches.show', $branch));

        $this->actingAs($owner)
            ->post(route('branches.follow-up.reopen', $branch))
            ->assertRedirect(route('branches.show', $branch));

        $followUp = OperatorFollowUp::query()
            ->where('followable_type', Branch::class)
            ->where('followable_id', $branch->id)
            ->firstOrFail();

        $this->assertSame('needs_follow_up', $followUp->status->value);
        $this->assertNull($followUp->resolved_by_user_id);
        $this->assertNull($followUp->resolved_at);

        $this->assertDatabaseHas('operator_notes', [
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
            'user_id' => $owner->id,
            'noteable_type' => Branch::class,
            'noteable_id' => $branch->id,
            'note' => 'Amina Juma reopened this follow-up.',
        ]);
    }

    public function test_other_tenant_user_cannot_acknowledge_someone_elses_follow_up(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $branch = Branch::query()->where('code', 'MWG')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('branches.follow-up.acknowledge', $branch))
            ->assertRedirect(route('branches.show', $branch));

        $followUp = OperatorFollowUp::query()
            ->where('followable_type', Branch::class)
            ->where('followable_id', $branch->id)
            ->firstOrFail();

        $this->assertNull($followUp->acknowledged_by_user_id);
        $this->assertNull($followUp->acknowledged_at);
    }

    public function test_tenant_user_cannot_take_other_tenant_follow_up(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenantTransaction = Transaction::query()->where('reference', 'TXN-2001')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('transactions.follow-up.store', $otherTenantTransaction))
            ->assertNotFound();

        $this->assertSame(
            0,
            OperatorNote::query()
                ->where('noteable_type', Transaction::class)
                ->where('noteable_id', $otherTenantTransaction->id)
                ->where('note', 'Amina Juma took ownership of this follow-up.')
                ->count()
        );
    }

    public function test_platform_admin_can_take_follow_up_without_tenant_membership(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $transaction = Transaction::query()->where('reference', 'TXN-1003')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('transactions.follow-up.store', $transaction), [
                'assigned_user_id' => $admin->id,
            ])
            ->assertRedirect(route('transactions.show', $transaction));

        $followUp = OperatorFollowUp::query()
            ->where('followable_type', Transaction::class)
            ->where('followable_id', $transaction->id)
            ->firstOrFail();

        $this->assertSame($admin->id, $followUp->assigned_user_id);
        $this->assertSame($admin->id, $followUp->assigned_by_user_id);
    }
}
