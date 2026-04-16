<?php

namespace App\Http\Controllers;

use App\Enums\HotspotSessionStatus;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\HotspotSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionShowController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request, HotspotSession $session): Response
    {
        $scope = $this->resolveWorkspaceScope($request);

        $session = HotspotSession::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->with([
                'tenant:id,name',
                'branch:id,name,code,location,address,manager_user_id',
                'branch.manager:id,name,email',
                'accessPackage:id,name,description,price,currency,duration_minutes,data_limit_mb,speed_limit_kbps',
                'voucher:id,code,status,locked_mac_address,redeemed_at,expires_at',
                'transaction:id,reference,status,source,amount,gateway_fee,currency,created_at,confirmed_at,provider_reference',
                'authorizer:id,name,email',
                'terminator:id,name,email',
            ])
            ->findOrFail($session->id);

        $expiryDeltaMinutes = $session->expires_at
            ? (int) ceil(now()->diffInSeconds($session->expires_at, false) / 60)
            : null;
        $startedAgeMinutes = $session->started_at
            ? (int) ceil($session->started_at->diffInSeconds(now()) / 60)
            : null;
        $usagePercentage = ($session->data_limit_mb && $session->data_limit_mb > 0)
            ? (int) min(100, round(($session->data_used_mb / $session->data_limit_mb) * 100))
            : null;

        return Inertia::render('operations/session-show', [
            'viewer' => $scope['viewer'],
            'session' => [
                'id' => $session->id,
                'status' => $session->status->value,
                'status_note' => match ($session->status) {
                    HotspotSessionStatus::Active => 'The customer has an active access session. Use the timing and usage details below to confirm the session is behaving as expected.',
                    HotspotSessionStatus::Pending => 'The access session exists but is still waiting on fulfilment or follow-up. Check the linked transaction before retrying payment.',
                    HotspotSessionStatus::Expired => 'This session already ran out. Use the linked voucher or transaction evidence if the customer is disputing access history.',
                    HotspotSessionStatus::Terminated => 'This session was ended before natural expiry. Review the linked transaction and branch context for operator or device-side causes.',
                },
                'tenant' => $session->tenant?->name,
                'branch' => [
                    'name' => $session->branch?->name,
                    'code' => $session->branch?->code,
                    'location' => $session->branch?->location,
                    'address' => $session->branch?->address,
                    'manager_name' => $session->branch?->manager?->name,
                    'manager_email' => $session->branch?->manager?->email,
                ],
                'package' => $session->accessPackage ? [
                    'name' => $session->accessPackage->name,
                    'description' => $session->accessPackage->description,
                    'price' => (float) $session->accessPackage->price,
                    'currency' => $session->accessPackage->currency,
                    'duration_minutes' => $session->accessPackage->duration_minutes,
                    'data_limit_mb' => $session->accessPackage->data_limit_mb,
                    'speed_limit_kbps' => $session->accessPackage->speed_limit_kbps,
                ] : null,
                'voucher' => $session->voucher ? [
                    'code' => $session->voucher->code,
                    'status' => $session->voucher->status->value,
                    'locked_mac_address' => $session->voucher->locked_mac_address,
                    'redeemed_at' => $session->voucher->redeemed_at?->toIso8601String(),
                    'expires_at' => $session->voucher->expires_at?->toIso8601String(),
                ] : null,
                'transaction' => $session->transaction ? [
                    'id' => $session->transaction->id,
                    'reference' => $session->transaction->reference,
                    'status' => $session->transaction->status->value,
                    'source' => $session->transaction->source->value,
                    'amount' => (float) $session->transaction->amount,
                    'gateway_fee' => (float) $session->transaction->gateway_fee,
                    'currency' => $session->transaction->currency,
                    'provider_reference' => $session->transaction->provider_reference,
                    'created_at' => $session->transaction->created_at?->toIso8601String(),
                    'confirmed_at' => $session->transaction->confirmed_at?->toIso8601String(),
                    'needs_follow_up' => $session->transaction->status === TransactionStatus::Pending,
                ] : null,
                'authorizer' => $session->authorizer ? [
                    'name' => $session->authorizer->name,
                    'email' => $session->authorizer->email,
                ] : null,
                'terminator' => $session->terminator ? [
                    'name' => $session->terminator->name,
                    'email' => $session->terminator->email,
                ] : null,
                'mac_address' => $session->device_mac_address,
                'ip_address' => $session->device_ip_address,
                'duration_minutes' => $session->duration_minutes,
                'data_limit_mb' => $session->data_limit_mb,
                'data_used_mb' => (int) $session->data_used_mb,
                'usage_percentage' => $usagePercentage,
                'started_at' => $session->started_at?->toIso8601String(),
                'expires_at' => $session->expires_at?->toIso8601String(),
                'ended_at' => $session->ended_at?->toIso8601String(),
                'started_age_minutes' => $startedAgeMinutes,
                'expires_in_minutes' => $expiryDeltaMinutes !== null && $expiryDeltaMinutes > 0 ? $expiryDeltaMinutes : null,
                'expired_since_minutes' => $expiryDeltaMinutes !== null && $expiryDeltaMinutes < 0 ? abs($expiryDeltaMinutes) : null,
                'termination_reason' => $session->termination_reason,
                'can_terminate' => in_array($session->status, [HotspotSessionStatus::Active, HotspotSessionStatus::Pending], true),
            ],
        ]);
    }
}
