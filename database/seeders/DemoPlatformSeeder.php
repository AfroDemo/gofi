<?php

namespace Database\Seeders;

use App\Actions\Finance\CreateRevenueAllocation;
use App\Enums\DeviceStatus;
use App\Enums\HotspotSessionStatus;
use App\Enums\LedgerDirection;
use App\Enums\PackageType;
use App\Enums\PayoutStatus;
use App\Enums\PlatformRole;
use App\Enums\RevenueShareModel;
use App\Enums\TenantStatus;
use App\Enums\TenantUserRole;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\VoucherStatus;
use App\Models\AccessPackage;
use App\Models\Branch;
use App\Models\HotspotDevice;
use App\Models\HotspotSession;
use App\Models\LedgerEntry;
use App\Models\PaymentCallback;
use App\Models\Payout;
use App\Models\RevenueShareRule;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DemoPlatformSeeder extends Seeder
{
    public function __construct(
        protected CreateRevenueAllocation $createRevenueAllocation,
    ) {}

    public function run(): void
    {
        $now = Carbon::now();
        $balances = [];

        $admin = User::create([
            'name' => 'GoFi Admin',
            'email' => 'admin@gofi.test',
            'password' => 'password',
            'platform_role' => PlatformRole::SuperAdmin,
            'email_verified_at' => $now,
        ]);

        $coastOwner = User::create([
            'name' => 'Amina Juma',
            'email' => 'amina@coastfi.test',
            'password' => 'password',
            'platform_role' => PlatformRole::TenantUser,
            'email_verified_at' => $now,
        ]);

        $coastOperator = User::create([
            'name' => 'Moses Ally',
            'email' => 'moses@coastfi.test',
            'password' => 'password',
            'platform_role' => PlatformRole::TenantUser,
            'email_verified_at' => $now,
        ]);

        $cityOwner = User::create([
            'name' => 'Neema Joseph',
            'email' => 'neema@citywave.test',
            'password' => 'password',
            'platform_role' => PlatformRole::TenantUser,
            'email_verified_at' => $now,
        ]);

        $coastTenant = Tenant::create([
            'name' => 'CoastFi Networks',
            'slug' => 'coastfi-networks',
            'status' => TenantStatus::Active,
            'currency' => 'TZS',
            'country_code' => 'TZ',
            'timezone' => 'Africa/Dar_es_Salaam',
            'owner_user_id' => $coastOwner->id,
            'created_by_user_id' => $admin->id,
        ]);

        $cityTenant = Tenant::create([
            'name' => 'CityWave Hotspots',
            'slug' => 'citywave-hotspots',
            'status' => TenantStatus::Active,
            'currency' => 'TZS',
            'country_code' => 'TZ',
            'timezone' => 'Africa/Dar_es_Salaam',
            'owner_user_id' => $cityOwner->id,
            'created_by_user_id' => $admin->id,
        ]);

        $coastTenant->memberships()->createMany([
            [
                'user_id' => $coastOwner->id,
                'role' => TenantUserRole::Owner,
                'is_primary' => true,
            ],
            [
                'user_id' => $coastOperator->id,
                'role' => TenantUserRole::Operator,
                'is_primary' => false,
            ],
        ]);

        $cityTenant->memberships()->create([
            'user_id' => $cityOwner->id,
            'role' => TenantUserRole::Owner,
            'is_primary' => true,
        ]);

        $kariakoo = Branch::create([
            'tenant_id' => $coastTenant->id,
            'name' => 'Kariakoo Hub',
            'code' => 'KRK',
            'location' => 'Dar es Salaam',
            'address' => 'Aggrey Street, Kariakoo',
            'manager_user_id' => $coastOwner->id,
        ]);

        $mwenge = Branch::create([
            'tenant_id' => $coastTenant->id,
            'name' => 'Mwenge Corner',
            'code' => 'MWG',
            'location' => 'Dar es Salaam',
            'address' => 'Sam Nujoma Road, Mwenge',
            'manager_user_id' => $coastOperator->id,
        ]);

        $mwanza = Branch::create([
            'tenant_id' => $cityTenant->id,
            'name' => 'Mlimani Point',
            'code' => 'MLM',
            'location' => 'Mwanza',
            'address' => 'Capri Point, Mwanza',
            'manager_user_id' => $cityOwner->id,
        ]);

        foreach ([
            [$coastTenant, $kariakoo, 'KRK-RTR-01', DeviceStatus::Online, '10.10.1.1'],
            [$coastTenant, $mwenge, 'MWG-RTR-01', DeviceStatus::Offline, '10.10.2.1'],
            [$cityTenant, $mwanza, 'MLM-RTR-01', DeviceStatus::Online, '10.20.1.1'],
            [$cityTenant, $mwanza, 'MLM-RTR-02', DeviceStatus::Provisioning, null],
        ] as [$tenant, $branch, $identifier, $status, $ipAddress]) {
            HotspotDevice::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'name' => Str::replace('-', ' ', $identifier),
                'identifier' => $identifier,
                'status' => $status,
                'integration_driver' => 'router_stub',
                'ip_address' => $ipAddress,
                'last_seen_at' => $status === DeviceStatus::Online ? $now->copy()->subMinutes(5) : null,
                'metadata' => ['firmware' => 'stub-1.0'],
            ]);
        }

        $coastHour = AccessPackage::create([
            'tenant_id' => $coastTenant->id,
            'branch_id' => $kariakoo->id,
            'name' => 'Coast Quick Hour',
            'package_type' => PackageType::Time,
            'description' => '1 hour social and browsing access',
            'price' => 1000,
            'currency' => 'TZS',
            'duration_minutes' => 60,
            'speed_limit_kbps' => 4096,
            'is_active' => true,
        ]);

        $coastDay = AccessPackage::create([
            'tenant_id' => $coastTenant->id,
            'branch_id' => $mwenge->id,
            'name' => 'Coast Day Pass',
            'package_type' => PackageType::Mixed,
            'description' => '24 hour pass with fair use cap',
            'price' => 3000,
            'currency' => 'TZS',
            'duration_minutes' => 1440,
            'data_limit_mb' => 5000,
            'speed_limit_kbps' => 6144,
            'is_active' => true,
        ]);

        $cityFlex = AccessPackage::create([
            'tenant_id' => $cityTenant->id,
            'branch_id' => $mwanza->id,
            'name' => 'City Flex 2 Hours',
            'package_type' => PackageType::Mixed,
            'description' => '2 hour pass for commuters and cafes',
            'price' => 2500,
            'currency' => 'TZS',
            'duration_minutes' => 120,
            'data_limit_mb' => 2500,
            'speed_limit_kbps' => 5120,
            'is_active' => true,
        ]);

        $coastRule = RevenueShareRule::create([
            'tenant_id' => $coastTenant->id,
            'name' => 'Coast default split',
            'model' => RevenueShareModel::Percentage,
            'platform_percentage' => 18,
            'platform_fixed_fee' => 0,
            'is_active' => true,
        ]);

        $cityRule = RevenueShareRule::create([
            'tenant_id' => $cityTenant->id,
            'name' => 'City hybrid split',
            'model' => RevenueShareModel::Hybrid,
            'platform_percentage' => 10,
            'platform_fixed_fee' => 500,
            'is_active' => true,
        ]);

        $coastProfile = VoucherProfile::create([
            'tenant_id' => $coastTenant->id,
            'branch_id' => $kariakoo->id,
            'access_package_id' => $coastHour->id,
            'name' => 'Quick Hour Scratch Card',
            'code_prefix' => 'CFH',
            'price' => 1000,
            'duration_minutes' => 60,
            'speed_limit_kbps' => 4096,
            'expires_in_days' => 30,
            'mac_lock_on_first_use' => true,
            'is_active' => true,
        ]);

        $cityProfile = VoucherProfile::create([
            'tenant_id' => $cityTenant->id,
            'branch_id' => $mwanza->id,
            'access_package_id' => $cityFlex->id,
            'name' => 'City Flex Voucher',
            'code_prefix' => 'CWX',
            'price' => 2500,
            'duration_minutes' => 120,
            'data_limit_mb' => 2500,
            'speed_limit_kbps' => 5120,
            'expires_in_days' => 14,
            'mac_lock_on_first_use' => true,
            'is_active' => true,
        ]);

        $usedVoucher = Voucher::create([
            'tenant_id' => $coastTenant->id,
            'branch_id' => $kariakoo->id,
            'voucher_profile_id' => $coastProfile->id,
            'access_package_id' => $coastHour->id,
            'code' => 'CFH-1001',
            'status' => VoucherStatus::Used,
            'locked_mac_address' => 'AA:BB:CC:DD:EE:01',
            'redeemed_at' => $now->copy()->subHours(8),
            'expires_at' => $now->copy()->addDays(20),
            'created_by_user_id' => $coastOperator->id,
            'redeemed_by_user_id' => $coastOperator->id,
        ]);

        foreach ([
            [$coastTenant, $kariakoo, $coastProfile, $coastHour, 'CFH-1002', VoucherStatus::Unused],
            [$coastTenant, $mwenge, $coastProfile, $coastHour, 'CFH-1003', VoucherStatus::Unused],
            [$cityTenant, $mwanza, $cityProfile, $cityFlex, 'CWX-2001', VoucherStatus::Unused],
            [$cityTenant, $mwanza, $cityProfile, $cityFlex, 'CWX-2002', VoucherStatus::Expired],
        ] as [$tenant, $branch, $profile, $package, $code, $status]) {
            Voucher::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'voucher_profile_id' => $profile->id,
                'access_package_id' => $package->id,
                'code' => $code,
                'status' => $status,
                'expires_at' => $status === VoucherStatus::Expired ? $now->copy()->subDay() : $now->copy()->addDays(14),
                'created_by_user_id' => $tenant->owner_user_id,
            ]);
        }

        $coastPaid = $this->createTransaction([
            'tenant' => $coastTenant,
            'branch' => $kariakoo,
            'package' => $coastHour,
            'rule' => $coastRule,
            'user' => $coastOperator,
            'source' => TransactionSource::MobileMoney,
            'status' => TransactionStatus::Successful,
            'reference' => 'TXN-1001',
            'phone_number' => '255712000111',
            'amount' => 1000,
            'gateway_fee' => 60,
            'paid_at' => $now->copy()->subHours(8),
            'confirmed_at' => $now->copy()->subHours(8)->addSeconds(30),
            'metadata' => ['provider' => 'mpesa'],
        ]);

        $coastVoucherSale = $this->createTransaction([
            'tenant' => $coastTenant,
            'branch' => $kariakoo,
            'package' => $coastHour,
            'voucher' => $usedVoucher,
            'rule' => $coastRule,
            'user' => $coastOperator,
            'source' => TransactionSource::Voucher,
            'status' => TransactionStatus::Successful,
            'reference' => 'TXN-1002',
            'amount' => 1000,
            'gateway_fee' => 0,
            'paid_at' => $now->copy()->subHours(7),
            'confirmed_at' => $now->copy()->subHours(7),
        ]);

        $coastPending = $this->createTransaction([
            'tenant' => $coastTenant,
            'branch' => $mwenge,
            'package' => $coastDay,
            'rule' => $coastRule,
            'user' => $coastOperator,
            'source' => TransactionSource::MobileMoney,
            'status' => TransactionStatus::Pending,
            'reference' => 'TXN-1003',
            'phone_number' => '255754000222',
            'amount' => 3000,
            'gateway_fee' => 90,
            'metadata' => ['provider' => 'tigopesa'],
        ]);

        $cityPaid = $this->createTransaction([
            'tenant' => $cityTenant,
            'branch' => $mwanza,
            'package' => $cityFlex,
            'rule' => $cityRule,
            'user' => $cityOwner,
            'source' => TransactionSource::MobileMoney,
            'status' => TransactionStatus::Successful,
            'reference' => 'TXN-2001',
            'phone_number' => '255743888111',
            'amount' => 2500,
            'gateway_fee' => 100,
            'paid_at' => $now->copy()->subHours(3),
            'confirmed_at' => $now->copy()->subHours(3)->addMinute(),
            'metadata' => ['provider' => 'airtelmoney'],
        ]);

        $this->createTransaction([
            'tenant' => $cityTenant,
            'branch' => $mwanza,
            'package' => $cityFlex,
            'rule' => $cityRule,
            'user' => $cityOwner,
            'source' => TransactionSource::MobileMoney,
            'status' => TransactionStatus::Failed,
            'reference' => 'TXN-2002',
            'phone_number' => '255743888999',
            'amount' => 2500,
            'gateway_fee' => 0,
            'metadata' => ['provider' => 'airtelmoney'],
        ]);

        foreach ([
            [$coastPaid, 'mpesa', 'payment.success', 'CB-1001', ['status' => 'successful']],
            [$coastPending, 'tigopesa', 'payment.pending', 'CB-1003', ['status' => 'pending']],
            [$cityPaid, 'airtelmoney', 'payment.success', 'CB-2001', ['status' => 'successful']],
        ] as [$transaction, $provider, $eventType, $callbackReference, $payload]) {
            PaymentCallback::create([
                'transaction_id' => $transaction->id,
                'provider' => $provider,
                'event_type' => $eventType,
                'callback_reference' => $callbackReference,
                'payload' => $payload,
                'received_at' => $now->copy()->subMinutes(30),
                'processed_at' => $now->copy()->subMinutes(29),
            ]);
        }

        foreach ([$coastPaid, $coastVoucherSale, $cityPaid] as $transaction) {
            $allocation = $this->createRevenueAllocation->execute($transaction, $transaction->revenueShareRule);
            $balances[$transaction->tenant_id] = ($balances[$transaction->tenant_id] ?? 0) + (float) $allocation->tenant_amount;

            LedgerEntry::create([
                'tenant_id' => $transaction->tenant_id,
                'transaction_id' => $transaction->id,
                'direction' => LedgerDirection::Credit,
                'entry_type' => 'sale',
                'amount' => $allocation->tenant_amount,
                'currency' => $transaction->currency,
                'balance_after' => $balances[$transaction->tenant_id],
                'description' => 'Tenant share for '.$transaction->reference,
                'posted_at' => $transaction->confirmed_at ?? $transaction->created_at,
            ]);
        }

        HotspotSession::create([
            'tenant_id' => $coastTenant->id,
            'branch_id' => $kariakoo->id,
            'access_package_id' => $coastHour->id,
            'transaction_id' => $coastPaid->id,
            'authorized_by_user_id' => $coastOperator->id,
            'device_mac_address' => 'AA:BB:CC:DD:EE:10',
            'device_ip_address' => '172.16.10.44',
            'status' => HotspotSessionStatus::Active,
            'duration_minutes' => 60,
            'data_used_mb' => 230,
            'started_at' => $now->copy()->subMinutes(20),
            'expires_at' => $now->copy()->addMinutes(40),
        ]);

        HotspotSession::create([
            'tenant_id' => $coastTenant->id,
            'branch_id' => $kariakoo->id,
            'access_package_id' => $coastHour->id,
            'voucher_id' => $usedVoucher->id,
            'transaction_id' => $coastVoucherSale->id,
            'authorized_by_user_id' => $coastOperator->id,
            'device_mac_address' => 'AA:BB:CC:DD:EE:01',
            'device_ip_address' => '172.16.10.12',
            'status' => HotspotSessionStatus::Expired,
            'duration_minutes' => 60,
            'data_used_mb' => 512,
            'started_at' => $now->copy()->subHours(8),
            'expires_at' => $now->copy()->subHours(7),
            'ended_at' => $now->copy()->subHours(7),
        ]);

        HotspotSession::create([
            'tenant_id' => $cityTenant->id,
            'branch_id' => $mwanza->id,
            'access_package_id' => $cityFlex->id,
            'transaction_id' => $cityPaid->id,
            'authorized_by_user_id' => $cityOwner->id,
            'device_mac_address' => 'AA:BB:CC:DD:EE:20',
            'device_ip_address' => '172.16.20.51',
            'status' => HotspotSessionStatus::Active,
            'duration_minutes' => 120,
            'data_limit_mb' => 2500,
            'data_used_mb' => 780,
            'started_at' => $now->copy()->subMinutes(50),
            'expires_at' => $now->copy()->addMinutes(70),
        ]);

        $coastPayout = Payout::create([
            'tenant_id' => $coastTenant->id,
            'amount' => 1200,
            'currency' => 'TZS',
            'period_start' => $now->copy()->startOfMonth()->subMonth(),
            'period_end' => $now->copy()->startOfMonth()->subDay(),
            'status' => PayoutStatus::Pending,
            'reference' => 'PAY-1001',
            'requested_at' => $now->copy()->subDays(2),
        ]);

        $cityPayout = Payout::create([
            'tenant_id' => $cityTenant->id,
            'amount' => 1100,
            'currency' => 'TZS',
            'period_start' => $now->copy()->startOfMonth()->subMonth(),
            'period_end' => $now->copy()->startOfMonth()->subDay(),
            'status' => PayoutStatus::Paid,
            'reference' => 'PAY-2001',
            'requested_at' => $now->copy()->subDays(5),
            'processed_at' => $now->copy()->subDays(3),
        ]);

        $balances[$cityTenant->id] -= 1100;

        LedgerEntry::create([
            'tenant_id' => $cityTenant->id,
            'payout_id' => $cityPayout->id,
            'direction' => LedgerDirection::Debit,
            'entry_type' => 'payout',
            'amount' => 1100,
            'currency' => 'TZS',
            'balance_after' => $balances[$cityTenant->id],
            'description' => 'Payout settled for prior cycle',
            'posted_at' => $cityPayout->processed_at,
        ]);

        $this->command?->info('Demo users: admin@gofi.test, amina@coastfi.test, moses@coastfi.test, neema@citywave.test');
        $this->command?->info('Demo password for all users: password');
        $this->command?->info('Seeded payouts: '.$coastPayout->reference.', '.$cityPayout->reference);
    }

    protected function createTransaction(array $attributes): Transaction
    {
        return Transaction::create([
            'tenant_id' => $attributes['tenant']->id,
            'branch_id' => $attributes['branch']->id,
            'access_package_id' => $attributes['package']->id,
            'voucher_id' => $attributes['voucher']->id ?? null,
            'revenue_share_rule_id' => $attributes['rule']->id,
            'initiated_by_user_id' => $attributes['user']->id,
            'source' => $attributes['source'],
            'status' => $attributes['status'],
            'reference' => $attributes['reference'],
            'provider_reference' => $attributes['reference'],
            'phone_number' => $attributes['phone_number'] ?? null,
            'amount' => $attributes['amount'],
            'gateway_fee' => $attributes['gateway_fee'],
            'currency' => 'TZS',
            'paid_at' => $attributes['paid_at'] ?? null,
            'confirmed_at' => $attributes['confirmed_at'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
        ]);
    }
}
