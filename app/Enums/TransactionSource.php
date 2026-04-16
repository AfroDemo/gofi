<?php

namespace App\Enums;

enum TransactionSource: string
{
    case MobileMoney = 'mobile_money';
    case Voucher = 'voucher';
    case Manual = 'manual';
}
