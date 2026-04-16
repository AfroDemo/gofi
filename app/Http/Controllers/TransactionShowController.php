<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionShowController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request, Transaction $transaction): Response
    {
        $scope = $this->resolveWorkspaceScope($request);

        $transaction = Transaction::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->with([
                'tenant:id,name',
                'branch:id,name,location',
                'accessPackage:id,name',
                'voucher:id,code',
                'initiator:id,name',
                'revenueShareRule:id,name,model,platform_percentage,platform_fixed_fee',
                'revenueAllocation:id,transaction_id,tenant_id,model,gross_amount,gateway_fee,platform_amount,tenant_amount,snapshot',
                'callbacks:id,transaction_id,provider,event_type,callback_reference,payload,received_at,processed_at',
                'hotspotSessions:id,transaction_id,branch_id,access_package_id,device_mac_address,device_ip_address,status,started_at,expires_at,ended_at,data_used_mb',
                'hotspotSessions.branch:id,name',
                'hotspotSessions.accessPackage:id,name',
                'ledgerEntries:id,tenant_id,transaction_id,direction,entry_type,amount,currency,balance_after,description,posted_at',
                'operatorFollowUp:id,tenant_id,branch_id,assigned_user_id,assigned_by_user_id,resolved_by_user_id,followable_type,followable_id,assigned_at,status,resolved_at',
                'operatorFollowUp.assignedUser:id,name,email',
                'operatorFollowUp.assignedBy:id,name,email',
                'operatorFollowUp.resolvedBy:id,name,email',
                'operatorNotes:id,tenant_id,branch_id,user_id,note,noteable_id,noteable_type,created_at',
                'operatorNotes.author:id,name,email',
            ])
            ->findOrFail($transaction->id);
        $assignableUsers = User::query()
            ->where(function ($query) use ($transaction, $request) {
                $query->whereHas('tenantMemberships', fn ($memberships) => $memberships->where('tenant_id', $transaction->tenant_id));

                if ($request->user()?->isPlatformAdmin()) {
                    $query->orWhere('id', $request->user()->id);
                }
            })
            ->orderBy('name')
            ->get()
            ->unique('id')
            ->values();
        $paymentMetadata = is_array($transaction->metadata) ? ($transaction->metadata['payment'] ?? []) : [];
        $pendingAgeMinutes = $transaction->status === TransactionStatus::Pending
            ? (int) ceil($transaction->created_at?->diffInSeconds(now()) / 60)
            : null;

        return Inertia::render('operations/transaction-show', [
            'viewer' => $scope['viewer'],
            'transaction' => [
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'provider_reference' => $transaction->provider_reference,
                'tenant' => $transaction->tenant?->name,
                'branch' => $transaction->branch?->name,
                'location' => $transaction->branch?->location,
                'package' => $transaction->accessPackage?->name,
                'voucher' => $transaction->voucher?->code,
                'initiated_by' => $transaction->initiator?->name,
                'source' => $transaction->source->value,
                'status' => $transaction->status->value,
                'phone_number' => $transaction->phone_number,
                'amount' => (float) $transaction->amount,
                'gateway_fee' => (float) $transaction->gateway_fee,
                'currency' => $transaction->currency,
                'confirmed_at' => $transaction->confirmed_at?->toIso8601String(),
                'paid_at' => $transaction->paid_at?->toIso8601String(),
                'created_at' => $transaction->created_at?->toIso8601String(),
                'metadata' => $transaction->metadata,
                'pending_age_minutes' => $pendingAgeMinutes,
                'payment' => [
                    'gateway' => data_get($paymentMetadata, 'gateway'),
                    'message' => data_get($paymentMetadata, 'message'),
                    'provider_reference' => $transaction->provider_reference,
                    'using_fallback' => (bool) data_get($paymentMetadata, 'selection.using_fallback', false),
                    'last_poll' => [
                        'gateway' => data_get($paymentMetadata, 'last_poll.gateway'),
                        'status' => data_get($paymentMetadata, 'last_poll.status'),
                        'checked_at' => data_get($paymentMetadata, 'last_poll.checked_at'),
                    ],
                    'attempts' => collect(data_get($paymentMetadata, 'attempts', []))
                        ->map(fn ($attempt) => [
                            'gateway' => data_get($attempt, 'gateway'),
                            'success' => (bool) data_get($attempt, 'success', false),
                            'message' => data_get($attempt, 'message'),
                        ])
                        ->values()
                        ->all(),
                    'can_check_status' => $transaction->status === TransactionStatus::Pending && filled($transaction->provider_reference),
                ],
                'revenue_rule' => $transaction->revenueShareRule ? [
                    'name' => $transaction->revenueShareRule->name,
                    'model' => $transaction->revenueShareRule->model->value,
                    'platform_percentage' => (float) $transaction->revenueShareRule->platform_percentage,
                    'platform_fixed_fee' => (float) $transaction->revenueShareRule->platform_fixed_fee,
                ] : null,
                'allocation' => $transaction->revenueAllocation ? [
                    'model' => $transaction->revenueAllocation->model,
                    'gross_amount' => (float) $transaction->revenueAllocation->gross_amount,
                    'gateway_fee' => (float) $transaction->revenueAllocation->gateway_fee,
                    'platform_amount' => (float) $transaction->revenueAllocation->platform_amount,
                    'tenant_amount' => (float) $transaction->revenueAllocation->tenant_amount,
                    'snapshot' => $transaction->revenueAllocation->snapshot,
                ] : null,
                'callbacks' => $transaction->callbacks
                    ->sortBy('received_at')
                    ->values()
                    ->map(fn ($callback) => [
                        'id' => $callback->id,
                        'provider' => $callback->provider,
                        'event_type' => $callback->event_type,
                        'callback_reference' => $callback->callback_reference,
                        'payload' => $callback->payload,
                        'received_at' => $callback->received_at?->toIso8601String(),
                        'processed_at' => $callback->processed_at?->toIso8601String(),
                    ]),
                'sessions' => $transaction->hotspotSessions
                    ->sortByDesc('started_at')
                    ->values()
                    ->map(fn ($session) => [
                        'id' => $session->id,
                        'branch' => $session->branch?->name,
                        'package' => $session->accessPackage?->name,
                        'mac_address' => $session->device_mac_address,
                        'ip_address' => $session->device_ip_address,
                        'status' => $session->status->value,
                        'started_at' => $session->started_at?->toIso8601String(),
                        'expires_at' => $session->expires_at?->toIso8601String(),
                        'ended_at' => $session->ended_at?->toIso8601String(),
                        'data_used_mb' => (int) $session->data_used_mb,
                    ]),
                'ledger_entries' => $transaction->ledgerEntries
                    ->sortBy('posted_at')
                    ->values()
                    ->map(fn ($entry) => [
                        'id' => $entry->id,
                        'direction' => $entry->direction->value,
                        'entry_type' => $entry->entry_type,
                        'amount' => (float) $entry->amount,
                        'currency' => $entry->currency,
                        'balance_after' => (float) $entry->balance_after,
                        'description' => $entry->description,
                        'posted_at' => $entry->posted_at?->toIso8601String(),
                    ]),
                'follow_up' => $transaction->operatorFollowUp ? [
                    'assigned_at' => $transaction->operatorFollowUp->assigned_at?->toIso8601String(),
                    'status' => $transaction->operatorFollowUp->status->value,
                    'resolved_at' => $transaction->operatorFollowUp->resolved_at?->toIso8601String(),
                    'owned_by_viewer' => $transaction->operatorFollowUp->assigned_user_id === $request->user()?->id,
                    'assigned_user_id' => $transaction->operatorFollowUp->assigned_user_id,
                    'assigned_user' => $transaction->operatorFollowUp->assignedUser ? [
                        'name' => $transaction->operatorFollowUp->assignedUser->name,
                        'email' => $transaction->operatorFollowUp->assignedUser->email,
                    ] : null,
                    'assigned_by' => $transaction->operatorFollowUp->assignedBy ? [
                        'name' => $transaction->operatorFollowUp->assignedBy->name,
                        'email' => $transaction->operatorFollowUp->assignedBy->email,
                    ] : null,
                    'resolved_by' => $transaction->operatorFollowUp->resolvedBy ? [
                        'name' => $transaction->operatorFollowUp->resolvedBy->name,
                        'email' => $transaction->operatorFollowUp->resolvedBy->email,
                    ] : null,
                ] : null,
                'assignable_users' => $assignableUsers->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->all(),
                'notes' => $transaction->operatorNotes
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
            ],
        ]);
    }
}
