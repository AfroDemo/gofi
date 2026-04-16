<?php

namespace App\Http\Controllers;

use App\Actions\Ops\AssignOperatorFollowUp;
use App\Actions\Ops\ReleaseOperatorFollowUp;
use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TransactionFollowUpController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __construct(
        protected AssignOperatorFollowUp $assignOperatorFollowUp,
        protected ReleaseOperatorFollowUp $releaseOperatorFollowUp,
    ) {}

    public function store(Request $request, Transaction $transaction): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $transaction = Transaction::query()->whereIn('tenant_id', $scope['tenant_ids'])->findOrFail($transaction->id);

        $this->assignOperatorFollowUp->execute($transaction, $request->user(), $request->user());

        return to_route('transactions.show', $transaction)->with('success', 'Follow-up ownership assigned to you.');
    }

    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);
        $transaction = Transaction::query()->whereIn('tenant_id', $scope['tenant_ids'])->with('operatorFollowUp')->findOrFail($transaction->id);

        $this->releaseOperatorFollowUp->execute($transaction, $request->user()?->name, $request->user()?->id);

        return to_route('transactions.show', $transaction)->with('success', 'Follow-up ownership released.');
    }
}
