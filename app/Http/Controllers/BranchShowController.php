<?php

namespace App\Http\Controllers;

use App\Enums\BranchStatus;
use App\Enums\DeviceIncidentStatus;
use App\Enums\DeviceStatus;
use App\Enums\HotspotSessionStatus;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Branch;
use App\Models\DeviceIncident;
use App\Models\HotspotDevice;
use App\Models\HotspotSession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchShowController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request, Branch $branch): Response
    {
        $scope = $this->resolveWorkspaceScope($request);

        $branch = Branch::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->with([
                'tenant:id,name,currency',
                'manager:id,name,email',
                'statusEvents:id,tenant_id,branch_id,changed_by_user_id,from_status,to_status,reason,created_at',
                'statusEvents.actor:id,name,email',
                'operatorFollowUp:id,tenant_id,branch_id,assigned_user_id,assigned_by_user_id,resolved_by_user_id,followable_type,followable_id,assigned_at,status,resolved_at',
                'operatorFollowUp.assignedUser:id,name,email',
                'operatorFollowUp.assignedBy:id,name,email',
                'operatorFollowUp.resolvedBy:id,name,email',
                'operatorNotes:id,tenant_id,branch_id,user_id,note,noteable_id,noteable_type,created_at',
                'operatorNotes.author:id,name,email',
            ])
            ->findOrFail($branch->id);

        $recentTransactions = Transaction::query()
            ->where('branch_id', $branch->id)
            ->with(['accessPackage:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        $recentSessions = HotspotSession::query()
            ->where('branch_id', $branch->id)
            ->with(['accessPackage:id,name', 'transaction:id,reference'])
            ->latest('started_at')
            ->limit(5)
            ->get();

        $recentIncidents = DeviceIncident::query()
            ->where('branch_id', $branch->id)
            ->with(['device:id,name,identifier', 'reporter:id,name', 'resolver:id,name'])
            ->latest('opened_at')
            ->limit(5)
            ->get();

        $assignableUsers = User::query()
            ->where(function ($query) use ($branch, $request) {
                $query->whereHas('tenantMemberships', fn ($memberships) => $memberships->where('tenant_id', $branch->tenant_id));

                if ($request->user()?->isPlatformAdmin()) {
                    $query->orWhere('id', $request->user()->id);
                }
            })
            ->orderBy('name')
            ->get()
            ->unique('id')
            ->values();

        return Inertia::render('operations/branch-show', [
            'viewer' => $scope['viewer'],
            'branch' => [
                'id' => $branch->id,
                'tenant' => $branch->tenant?->name,
                'name' => $branch->name,
                'code' => $branch->code,
                'status' => $branch->status->value,
                'status_note' => match ($branch->status) {
                    BranchStatus::Active => 'This branch is available for normal hotspot operations.',
                    BranchStatus::Maintenance => 'This branch is in maintenance mode. Operators should treat payment and access problems as potentially branch-wide.',
                    BranchStatus::Inactive => 'This branch is inactive and should not be relied on for live customer operations.',
                },
                'location' => $branch->location,
                'address' => $branch->address,
                'currency' => $branch->tenant?->currency,
                'manager' => [
                    'name' => $branch->manager?->name,
                    'email' => $branch->manager?->email,
                ],
            ],
            'status_options' => collect(BranchStatus::cases())
                ->map(fn (BranchStatus $status) => [
                    'value' => $status->value,
                    'label' => str($status->value)->headline()->toString(),
                ])
                ->all(),
            'summary' => [
                'devices' => HotspotDevice::query()->where('branch_id', $branch->id)->count(),
                'online_devices' => HotspotDevice::query()->where('branch_id', $branch->id)->where('status', DeviceStatus::Online)->count(),
                'active_sessions' => HotspotSession::query()->where('branch_id', $branch->id)->where('status', HotspotSessionStatus::Active)->count(),
                'pending_transactions' => Transaction::query()->where('branch_id', $branch->id)->where('status', TransactionStatus::Pending)->count(),
                'open_incidents' => DeviceIncident::query()->where('branch_id', $branch->id)->where('status', DeviceIncidentStatus::Open)->count(),
                'successful_revenue' => (float) Transaction::query()->where('branch_id', $branch->id)->where('status', TransactionStatus::Successful)->sum('amount'),
            ],
            'status_history' => $branch->statusEvents
                ->sortByDesc('created_at')
                ->values()
                ->map(fn ($event) => [
                    'id' => $event->id,
                    'from_status' => $event->from_status,
                    'to_status' => $event->to_status,
                    'reason' => $event->reason,
                    'created_at' => $event->created_at?->toIso8601String(),
                    'actor' => $event->actor ? [
                        'name' => $event->actor->name,
                        'email' => $event->actor->email,
                    ] : null,
                ])
                ->all(),
            'recent_transactions' => $recentTransactions->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'status' => $transaction->status->value,
                'source' => $transaction->source->value,
                'amount' => (float) $transaction->amount,
                'currency' => $transaction->currency,
                'package' => $transaction->accessPackage?->name,
                'created_at' => $transaction->created_at?->toIso8601String(),
            ])->values(),
            'recent_sessions' => $recentSessions->map(fn (HotspotSession $session) => [
                'id' => $session->id,
                'package' => $session->accessPackage?->name,
                'status' => $session->status->value,
                'transaction_reference' => $session->transaction?->reference,
                'started_at' => $session->started_at?->toIso8601String(),
                'expires_at' => $session->expires_at?->toIso8601String(),
            ])->values(),
            'recent_incidents' => $recentIncidents->map(fn (DeviceIncident $incident) => [
                'id' => $incident->id,
                'title' => $incident->title,
                'status' => $incident->status->value,
                'severity' => $incident->severity->value,
                'device_name' => $incident->device?->name,
                'device_identifier' => $incident->device?->identifier,
                'reporter_name' => $incident->reporter?->name,
                'resolver_name' => $incident->resolver?->name,
                'opened_at' => $incident->opened_at?->toIso8601String(),
                'resolved_at' => $incident->resolved_at?->toIso8601String(),
            ])->values(),
            'follow_up' => $branch->operatorFollowUp ? [
                'assigned_at' => $branch->operatorFollowUp->assigned_at?->toIso8601String(),
                'status' => $branch->operatorFollowUp->status->value,
                'resolved_at' => $branch->operatorFollowUp->resolved_at?->toIso8601String(),
                'owned_by_viewer' => $branch->operatorFollowUp->assigned_user_id === $request->user()?->id,
                'assigned_user_id' => $branch->operatorFollowUp->assigned_user_id,
                'assigned_user' => $branch->operatorFollowUp->assignedUser ? [
                    'name' => $branch->operatorFollowUp->assignedUser->name,
                    'email' => $branch->operatorFollowUp->assignedUser->email,
                ] : null,
                'assigned_by' => $branch->operatorFollowUp->assignedBy ? [
                    'name' => $branch->operatorFollowUp->assignedBy->name,
                    'email' => $branch->operatorFollowUp->assignedBy->email,
                ] : null,
                'resolved_by' => $branch->operatorFollowUp->resolvedBy ? [
                    'name' => $branch->operatorFollowUp->resolvedBy->name,
                    'email' => $branch->operatorFollowUp->resolvedBy->email,
                ] : null,
            ] : null,
            'assignable_users' => $assignableUsers->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])->all(),
            'notes' => $branch->operatorNotes
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
