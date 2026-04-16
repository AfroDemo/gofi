<?php

namespace App\Actions\Finance;

use App\Enums\RevenueShareModel;
use App\Models\RevenueAllocation;
use App\Models\RevenueShareRule;
use App\Models\Transaction;

class CreateRevenueAllocation
{
    public function execute(Transaction $transaction, RevenueShareRule $rule): RevenueAllocation
    {
        $grossAmount = (float) $transaction->amount;
        $gatewayFee = (float) $transaction->gateway_fee;
        $netAmount = max($grossAmount - $gatewayFee, 0);

        $platformAmount = match ($rule->model) {
            RevenueShareModel::Percentage => $netAmount * ((float) $rule->platform_percentage / 100),
            RevenueShareModel::FixedFee => min($netAmount, (float) $rule->platform_fixed_fee),
            RevenueShareModel::Hybrid => min(
                $netAmount,
                (float) $rule->platform_fixed_fee + ($netAmount * ((float) $rule->platform_percentage / 100))
            ),
        };

        $platformAmount = round($platformAmount, 2);
        $tenantAmount = round(max($netAmount - $platformAmount, 0), 2);

        return RevenueAllocation::updateOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'tenant_id' => $transaction->tenant_id,
                'model' => $rule->model->value,
                'gross_amount' => $grossAmount,
                'gateway_fee' => $gatewayFee,
                'platform_amount' => $platformAmount,
                'tenant_amount' => $tenantAmount,
                'snapshot' => [
                    'rule_id' => $rule->id,
                    'platform_percentage' => (float) $rule->platform_percentage,
                    'platform_fixed_fee' => (float) $rule->platform_fixed_fee,
                ],
            ]
        );
    }
}
