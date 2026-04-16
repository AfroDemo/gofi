<?php

namespace App\Http\Controllers;

use App\Enums\DeviceIncidentStatus;
use App\Enums\DeviceStatus;
use App\Enums\HotspotSessionStatus;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\AccessPackage;
use App\Models\DeviceIncident;
use App\Models\HotspotDevice;
use App\Models\HotspotSession;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeviceShowController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request, HotspotDevice $device): Response
    {
        $scope = $this->resolveWorkspaceScope($request);

        $device = HotspotDevice::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->with([
                'tenant:id,name',
                'branch:id,tenant_id,name,code,location,address,manager_user_id',
                'branch.manager:id,name,email',
                'incidents:id,tenant_id,branch_id,hotspot_device_id,reported_by_user_id,resolved_by_user_id,title,details,severity,status,opened_at,resolved_at,resolution_notes',
                'incidents.reporter:id,name,email',
                'incidents.resolver:id,name,email',
                'operatorFollowUp:id,tenant_id,branch_id,assigned_user_id,assigned_by_user_id,followable_type,followable_id,assigned_at',
                'operatorFollowUp.assignedUser:id,name,email',
                'operatorFollowUp.assignedBy:id,name,email',
                'operatorNotes:id,tenant_id,branch_id,user_id,note,noteable_id,noteable_type,created_at',
                'operatorNotes.author:id,name,email',
            ])
            ->findOrFail($device->id);

        $branchId = $device->branch_id;
        $lastSeenAgeMinutes = $device->last_seen_at
            ? (int) ceil($device->last_seen_at->diffInSeconds(now()) / 60)
            : null;

        $recentSessions = HotspotSession::query()
            ->where('branch_id', $branchId)
            ->with(['accessPackage:id,name', 'transaction:id,reference'])
            ->latest('started_at')
            ->limit(5)
            ->get();

        $recentTransactions = Transaction::query()
            ->where('branch_id', $branchId)
            ->with(['accessPackage:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('operations/device-show', [
            'viewer' => $scope['viewer'],
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'identifier' => $device->identifier,
                'status' => $device->status->value,
                'integration_driver' => $device->integration_driver,
                'ip_address' => $device->ip_address,
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
                'last_seen_age_minutes' => $lastSeenAgeMinutes,
                'status_note' => match ($device->status) {
                    DeviceStatus::Online => 'This device is reporting normally and should be ready for customer traffic.',
                    DeviceStatus::Offline => 'This device is not reporting in. Check branch power, uplink, and router reachability before blaming checkout.',
                    DeviceStatus::Provisioning => 'This device is not fully operational yet. Finish provisioning before relying on it for live sessions.',
                },
                'metadata' => $device->metadata,
                'tenant' => $device->tenant?->name,
                'branch' => [
                    'name' => $device->branch?->name,
                    'code' => $device->branch?->code,
                    'location' => $device->branch?->location,
                    'address' => $device->branch?->address,
                    'manager_name' => $device->branch?->manager?->name,
                    'manager_email' => $device->branch?->manager?->email,
                ],
            ],
            'branch_overview' => [
                'device_count' => HotspotDevice::query()->where('branch_id', $branchId)->count(),
                'online_devices' => HotspotDevice::query()->where('branch_id', $branchId)->where('status', DeviceStatus::Online)->count(),
                'active_sessions' => HotspotSession::query()->where('branch_id', $branchId)->where('status', HotspotSessionStatus::Active)->count(),
                'pending_transactions' => Transaction::query()->where('branch_id', $branchId)->where('status', TransactionStatus::Pending)->count(),
                'stale_pending_transactions' => Transaction::query()
                    ->where('branch_id', $branchId)
                    ->where('status', TransactionStatus::Pending)
                    ->where('created_at', '<=', now()->subMinutes(5))
                    ->count(),
                'active_packages' => AccessPackage::query()->where('branch_id', $branchId)->where('is_active', true)->count(),
                'open_incidents' => DeviceIncident::query()
                    ->where('hotspot_device_id', $device->id)
                    ->where('status', DeviceIncidentStatus::Open)
                    ->count(),
            ],
            'recent_sessions' => $recentSessions->map(fn (HotspotSession $session) => [
                'id' => $session->id,
                'package' => $session->accessPackage?->name,
                'status' => $session->status->value,
                'transaction_reference' => $session->transaction?->reference,
                'started_at' => $session->started_at?->toIso8601String(),
                'expires_at' => $session->expires_at?->toIso8601String(),
                'data_used_mb' => (int) $session->data_used_mb,
            ])->values(),
            'recent_transactions' => $recentTransactions->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'status' => $transaction->status->value,
                'source' => $transaction->source->value,
                'amount' => (float) $transaction->amount,
                'currency' => $transaction->currency,
                'package' => $transaction->accessPackage?->name,
                'provider_reference' => $transaction->provider_reference,
                'created_at' => $transaction->created_at?->toIso8601String(),
            ])->values(),
            'incident_options' => [
                ['value' => 'low', 'label' => 'Low'],
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
                ['value' => 'critical', 'label' => 'Critical'],
            ],
            'incidents' => $device->incidents
                ->sortByDesc('opened_at')
                ->values()
                ->map(fn (DeviceIncident $incident) => [
                    'id' => $incident->id,
                    'title' => $incident->title,
                    'details' => $incident->details,
                    'severity' => $incident->severity->value,
                    'status' => $incident->status->value,
                    'opened_at' => $incident->opened_at?->toIso8601String(),
                    'resolved_at' => $incident->resolved_at?->toIso8601String(),
                    'resolution_notes' => $incident->resolution_notes,
                    'reporter' => $incident->reporter ? [
                        'name' => $incident->reporter->name,
                        'email' => $incident->reporter->email,
                    ] : null,
                    'resolver' => $incident->resolver ? [
                        'name' => $incident->resolver->name,
                        'email' => $incident->resolver->email,
                    ] : null,
                    'can_resolve' => $incident->status === DeviceIncidentStatus::Open,
                ])->all(),
            'follow_up' => $device->operatorFollowUp ? [
                'assigned_at' => $device->operatorFollowUp->assigned_at?->toIso8601String(),
                'owned_by_viewer' => $device->operatorFollowUp->assigned_user_id === $request->user()?->id,
                'assigned_user' => $device->operatorFollowUp->assignedUser ? [
                    'name' => $device->operatorFollowUp->assignedUser->name,
                    'email' => $device->operatorFollowUp->assignedUser->email,
                ] : null,
                'assigned_by' => $device->operatorFollowUp->assignedBy ? [
                    'name' => $device->operatorFollowUp->assignedBy->name,
                    'email' => $device->operatorFollowUp->assignedBy->email,
                ] : null,
            ] : null,
            'notes' => $device->operatorNotes
                ->sortByDesc('created_at')
                ->values()
                ->map(fn ($note) => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'created_at' => $note->created_at?->toIso8601String(),
                    'author' => $note->author ? [
                        'name' => $note->author->name,
                        'email' => $note->author->email,
                    ] : null,
                ])
                ->all(),
        ]);
    }
}
