<?php

namespace Tests\Feature;

use App\Enums\HotspotSessionStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\VoucherStatus;
use App\Models\AccessPackage;
use App\Models\Transaction;
use App\Models\Voucher;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_portal_page_loads_available_packages_for_branch(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $this->get(route('portal.show', ['tenantSlug' => 'coastfi-networks', 'branchCode' => 'KRK']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('portal/show', false)
                ->where('tenant.name', 'CoastFi Networks')
                ->where('branch.name', 'Kariakoo Hub')
                ->has('packages', 1)
                ->where('packages.0.name', 'Coast Quick Hour')
            );
    }

    public function test_mobile_money_checkout_creates_pending_transaction_from_public_portal(): void
    {
        $this->seed(DemoPlatformSeeder::class);
        $package = AccessPackage::query()->where('name', 'Coast Quick Hour')->firstOrFail();

        $response = $this->post(route('portal.checkout', ['tenantSlug' => 'coastfi-networks', 'branchCode' => 'KRK']), [
            'package_id' => $package->id,
            'phone_number' => '+255 712 000 555',
        ]);

        $transaction = Transaction::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('portal.transactions.show', [
            'tenantSlug' => 'coastfi-networks',
            'branchCode' => 'KRK',
            'reference' => $transaction->reference,
        ]));

        $this->assertSame(TransactionSource::MobileMoney, $transaction->source);
        $this->assertSame(TransactionStatus::Pending, $transaction->status);
        $this->assertSame('255712000555', $transaction->phone_number);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'tenant_id' => $package->tenant_id,
            'branch_id' => $package->branch_id,
            'status' => TransactionStatus::Pending->value,
        ]);
    }

    public function test_voucher_redemption_marks_voucher_used_and_creates_active_session(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $voucher = Voucher::query()->where('code', 'CFH-1002')->firstOrFail();

        $response = $this->post(route('portal.voucher.redeem', ['tenantSlug' => 'coastfi-networks', 'branchCode' => 'KRK']), [
            'voucher_code' => 'cfh-1002',
        ]);

        $voucher->refresh();
        $transaction = Transaction::query()
            ->where('voucher_id', $voucher->id)
            ->latest('id')
            ->firstOrFail();

        $response->assertRedirect(route('portal.transactions.show', [
            'tenantSlug' => 'coastfi-networks',
            'branchCode' => 'KRK',
            'reference' => $transaction->reference,
        ]));

        $this->assertSame(VoucherStatus::Used, $voucher->status);
        $this->assertSame(TransactionStatus::Successful, $transaction->status);
        $this->assertSame(TransactionSource::Voucher, $transaction->source);

        $this->assertDatabaseHas('hotspot_sessions', [
            'transaction_id' => $transaction->id,
            'voucher_id' => $voucher->id,
            'status' => HotspotSessionStatus::Active->value,
        ]);

        $this->assertDatabaseHas('revenue_allocations', [
            'transaction_id' => $transaction->id,
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'transaction_id' => $transaction->id,
            'entry_type' => 'sale',
        ]);
    }

    public function test_voucher_redemption_rejects_branch_mismatch(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $portalUrl = route('portal.show', ['tenantSlug' => 'coastfi-networks', 'branchCode' => 'KRK']);

        $this->from($portalUrl)
            ->post(route('portal.voucher.redeem', ['tenantSlug' => 'coastfi-networks', 'branchCode' => 'KRK']), [
                'voucher_code' => 'CFH-1003',
            ])
            ->assertRedirect($portalUrl)
            ->assertSessionHasErrors('voucher_code');
    }

    public function test_voucher_redemption_rejects_expired_voucher(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $portalUrl = route('portal.show', ['tenantSlug' => 'citywave-hotspots', 'branchCode' => 'MLM']);

        $this->from($portalUrl)
            ->post(route('portal.voucher.redeem', ['tenantSlug' => 'citywave-hotspots', 'branchCode' => 'MLM']), [
                'voucher_code' => 'CWX-2002',
            ])
            ->assertRedirect($portalUrl)
            ->assertSessionHasErrors('voucher_code');

        $this->assertDatabaseHas('vouchers', [
            'code' => 'CWX-2002',
            'status' => VoucherStatus::Expired->value,
        ]);
    }
}
