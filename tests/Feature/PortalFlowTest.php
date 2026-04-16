<?php

namespace Tests\Feature;

use App\Enums\HotspotSessionStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\VoucherStatus;
use App\Models\AccessPackage;
use App\Models\Branch;
use App\Models\RevenueShareRule;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\Voucher;
use Database\Seeders\DemoPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
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
                ->where('support.contact_name', 'Amina Juma')
                ->where('support.contact_role', 'Branch manager')
                ->has('guidance', 4)
                ->has('packages', 1)
                ->where('packages.0.name', 'Coast Quick Hour')
            );
    }

    public function test_transaction_status_page_exposes_retry_diagnostics_for_stale_pending_payment(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $tenant = Tenant::query()->where('slug', 'coastfi-networks')->firstOrFail();
        $branch = Branch::query()->where('tenant_id', $tenant->id)->where('code', 'KRK')->firstOrFail();
        $package = AccessPackage::query()->where('tenant_id', $tenant->id)->where('name', 'Coast Quick Hour')->firstOrFail();
        $rule = RevenueShareRule::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $transaction = Transaction::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'access_package_id' => $package->id,
            'revenue_share_rule_id' => $rule->id,
            'source' => TransactionSource::MobileMoney,
            'status' => TransactionStatus::Pending,
            'reference' => 'PRT-DIAG-100',
            'provider_reference' => 'SELCOM-DIAG-100',
            'phone_number' => '255712000111',
            'amount' => 1000,
            'gateway_fee' => 0,
            'currency' => 'TZS',
            'created_at' => Carbon::now()->subMinutes(8),
            'updated_at' => Carbon::now()->subMinutes(8),
            'metadata' => [
                'channel' => 'portal',
                'payment' => [
                    'gateway' => 'palmpesa',
                    'message' => 'Payment request sent to user phone.',
                    'selection' => [
                        'using_fallback' => false,
                    ],
                    'attempts' => [
                        [
                            'gateway' => 'palmpesa',
                            'success' => true,
                            'message' => 'Wallet push successful',
                        ],
                    ],
                ],
            ],
        ]);

        $transaction->timestamps = false;
        $transaction->forceFill([
            'created_at' => Carbon::now()->subMinutes(8),
            'updated_at' => Carbon::now()->subMinutes(8),
        ])->save();

        $this->get(route('portal.transactions.show', [
            'tenantSlug' => $tenant->slug,
            'branchCode' => $branch->code,
            'reference' => $transaction->reference,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('portal/transaction-status', false)
                ->where('transaction.reference', 'PRT-DIAG-100')
                ->where('support.contact_name', 'Amina Juma')
                ->where('transaction.state_hint', 'stale_pending')
                ->where('transaction.pending_age_minutes', fn ($value) => is_int($value) && $value >= 8)
                ->where('transaction.payment.gateway', 'palmpesa')
                ->where('transaction.payment.provider_reference', 'SELCOM-DIAG-100')
                ->where('transaction.payment.can_check_status', true)
                ->where('transaction.payment.can_restart', true)
                ->has('transaction.payment.attempts', 1)
            );
    }

    public function test_mobile_money_checkout_creates_pending_transaction_from_public_portal(): void
    {
        $this->seed(DemoPlatformSeeder::class);
        $package = AccessPackage::query()->where('name', 'Coast Quick Hour')->firstOrFail();

        config([
            'payment.gateway' => 'palmpesa',
            'payment.fallback_gateway' => 'snippe',
            'payment.palmpesa.enabled' => true,
            'payment.snippe.enabled' => false,
        ]);

        Http::fake([
            'https://palmpesa.drmlelwa.co.tz/api/pay-via-mobile' => Http::response([
                'message' => "Payment request sent to user's phone",
                'order_id' => 'SELCOM17458294939723',
                'response' => [
                    'reference' => 'S19997158895',
                    'transid' => 'TXN1745829493',
                    'resultcode' => '000',
                    'result' => 'SUCCESS',
                ],
            ], 200),
        ]);

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
        $this->assertSame('SELCOM17458294939723', $transaction->provider_reference);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'tenant_id' => $package->tenant_id,
            'branch_id' => $package->branch_id,
            'status' => TransactionStatus::Pending->value,
        ]);
    }

    public function test_portal_can_refresh_pending_payment_and_activate_session(): void
    {
        $this->seed(DemoPlatformSeeder::class);

        $tenant = Tenant::query()->where('slug', 'coastfi-networks')->firstOrFail();
        $branch = Branch::query()->where('tenant_id', $tenant->id)->where('code', 'KRK')->firstOrFail();
        $package = AccessPackage::query()->where('tenant_id', $tenant->id)->where('name', 'Coast Quick Hour')->firstOrFail();
        $rule = RevenueShareRule::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $transaction = Transaction::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'access_package_id' => $package->id,
            'revenue_share_rule_id' => $rule->id,
            'source' => TransactionSource::MobileMoney,
            'status' => TransactionStatus::Pending,
            'reference' => 'PRT-REFRESH-100',
            'provider_reference' => 'SELCOM-ORDER-100',
            'phone_number' => '255712000777',
            'amount' => 1000,
            'gateway_fee' => 0,
            'currency' => 'TZS',
            'metadata' => [
                'channel' => 'portal',
                'device_ip_address' => '127.0.0.1',
                'payment' => [
                    'gateway' => 'palmpesa',
                ],
            ],
        ]);

        config([
            'payment.gateway' => 'palmpesa',
            'payment.fallback_gateway' => 'snippe',
            'payment.palmpesa.enabled' => true,
            'payment.snippe.enabled' => false,
        ]);

        Http::fake([
            'https://palmpesa.drmlelwa.co.tz/api/order-status' => Http::response([
                'status' => 'completed',
                'order_id' => 'SELCOM-ORDER-100',
                'fee' => 60,
                'paid_at' => now()->toIso8601String(),
            ], 200),
        ]);

        $this->post(route('portal.transactions.refresh', [
            'tenantSlug' => 'coastfi-networks',
            'branchCode' => 'KRK',
            'reference' => $transaction->reference,
        ]))->assertRedirect();

        $transaction->refresh();

        $this->assertSame(TransactionStatus::Successful, $transaction->status);

        $this->assertDatabaseHas('hotspot_sessions', [
            'transaction_id' => $transaction->id,
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
