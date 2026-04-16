<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\RevenueAllocation;
use App\Models\Transaction;
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

        $transactions = Transaction::query()
            ->whereIn('tenant_id', $tenantIds)
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
            'summary' => [
                'gross_successful' => (float) $transactions
                    ->where('status', TransactionStatus::Successful)
                    ->sum('amount'),
                'pending_count' => $transactions->where('status', TransactionStatus::Pending)->count(),
                'failed_count' => $transactions->where('status', TransactionStatus::Failed)->count(),
                'platform_share' => (float) RevenueAllocation::query()->whereIn('tenant_id', $tenantIds)->sum('platform_amount'),
                'tenant_share' => (float) RevenueAllocation::query()->whereIn('tenant_id', $tenantIds)->sum('tenant_amount'),
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
