<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionIndexController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request): Response
    {
        $scope = $this->resolveWorkspaceScope($request);
        $tenantIds = $scope['tenant_ids'];
        $filters = [
            'search' => trim((string) $request->string('search')),
            'status' => in_array($request->string('status')->toString(), ['all', 'successful', 'pending', 'failed', 'cancelled'], true)
                ? $request->string('status')->toString()
                : 'all',
            'source' => in_array($request->string('source')->toString(), ['all', 'mobile_money', 'voucher', 'manual'], true)
                ? $request->string('source')->toString()
                : 'all',
        ];

        $transactions = Transaction::query()
            ->whereIn('tenant_id', $tenantIds)
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = '%'.$filters['search'].'%';

                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('reference', 'like', $search)
                        ->orWhere('provider_reference', 'like', $search)
                        ->orWhere('phone_number', 'like', $search)
                        ->orWhereHas('tenant', fn (Builder $tenant) => $tenant->where('name', 'like', $search))
                        ->orWhereHas('branch', fn (Builder $branch) => $branch->where('name', 'like', $search))
                        ->orWhereHas('accessPackage', fn (Builder $package) => $package->where('name', 'like', $search))
                        ->orWhereHas('initiator', fn (Builder $user) => $user->where('name', 'like', $search));
                });
            })
            ->when($filters['status'] !== 'all', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['source'] !== 'all', fn (Builder $query) => $query->where('source', $filters['source']))
            ->with([
                'tenant:id,name',
                'branch:id,name',
                'accessPackage:id,name',
                'initiator:id,name',
                'revenueAllocation:id,transaction_id,platform_amount,tenant_amount',
            ])
            ->latest()
            ->get();

        $sourceMix = $transactions
            ->groupBy(fn (Transaction $transaction) => $transaction->source->value)
            ->map(fn ($group, $source) => [
                'source' => $source,
                'count' => $group->count(),
                'amount' => (float) $group->sum('amount'),
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();

        return Inertia::render('operations/transactions', [
            'viewer' => $scope['viewer'],
            'filters' => $filters,
            'summary' => [
                'gross_successful' => (float) $transactions
                    ->where('status', TransactionStatus::Successful)
                    ->sum('amount'),
                'pending_count' => $transactions->where('status', TransactionStatus::Pending)->count(),
                'failed_count' => $transactions->where('status', TransactionStatus::Failed)->count(),
                'platform_share' => (float) $transactions->sum(fn (Transaction $transaction) => (float) ($transaction->revenueAllocation?->platform_amount ?? 0)),
                'tenant_share' => (float) $transactions->sum(fn (Transaction $transaction) => (float) ($transaction->revenueAllocation?->tenant_amount ?? 0)),
            ],
            'sourceMix' => $sourceMix,
            'transactions' => $transactions->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'tenant' => $transaction->tenant?->name,
                'branch' => $transaction->branch?->name,
                'package' => $transaction->accessPackage?->name,
                'initiated_by' => $transaction->initiator?->name,
                'source' => $transaction->source->value,
                'status' => $transaction->status->value,
                'phone_number' => $transaction->phone_number,
                'amount' => (float) $transaction->amount,
                'gateway_fee' => (float) $transaction->gateway_fee,
                'currency' => $transaction->currency,
                'platform_amount' => (float) ($transaction->revenueAllocation?->platform_amount ?? 0),
                'tenant_amount' => (float) ($transaction->revenueAllocation?->tenant_amount ?? 0),
                'confirmed_at' => $transaction->confirmed_at?->toIso8601String(),
                'created_at' => $transaction->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
