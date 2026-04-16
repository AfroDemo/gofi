<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransactionShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_transaction_detail(): void
    {
        $this->get('/transactions/1')->assertRedirect('/login');
    }

    public function test_platform_admin_can_view_transaction_detail_with_callbacks_and_allocation(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $admin = User::query()->where('email', 'admin@gofi.test')->firstOrFail();
        $transaction = Transaction::query()->where('reference', 'TXN-1001')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('transactions.show', $transaction))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('operations/transaction-show', false)
                ->where('transaction.reference', 'TXN-1001')
                ->where('transaction.status', 'successful')
                ->where('transaction.allocation.platform_amount', 169.2)
                ->has('transaction.callbacks', 1)
                ->has('transaction.sessions', 1)
                ->has('transaction.ledger_entries', 1)
            );
    }

    public function test_tenant_user_cannot_view_other_tenant_transaction_detail(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $owner = User::query()->where('email', 'amina@coastfi.test')->firstOrFail();
        $otherTenantTransaction = Transaction::query()->where('reference', 'TXN-2001')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('transactions.show', $otherTenantTransaction))
            ->assertNotFound();
    }
}
