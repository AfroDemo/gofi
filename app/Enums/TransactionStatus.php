<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Successful = 'successful';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
