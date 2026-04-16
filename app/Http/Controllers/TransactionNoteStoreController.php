<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspaceScope;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TransactionNoteStoreController extends Controller
{
    use ResolvesWorkspaceScope;

    public function __invoke(Request $request, Transaction $transaction): RedirectResponse
    {
        $scope = $this->resolveWorkspaceScope($request);

        $transaction = Transaction::query()
            ->whereIn('tenant_id', $scope['tenant_ids'])
            ->findOrFail($transaction->id);

        $payload = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $transaction->operatorNotes()->create([
            'tenant_id' => $transaction->tenant_id,
            'branch_id' => $transaction->branch_id,
            'user_id' => $request->user()?->id,
            'note' => $payload['note'],
        ]);

        return to_route('transactions.show', $transaction)->with('success', 'Operator follow-up note saved.');
    }
}
