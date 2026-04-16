<?php

namespace App\Enums;

enum RevenueShareModel: string
{
    case Percentage = 'percentage';
    case FixedFee = 'fixed_fee';
    case Hybrid = 'hybrid';
}
