<?php

namespace App\Enums;

enum VoucherStatus: string
{
    case Unused = 'unused';
    case Active = 'active';
    case Used = 'used';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
