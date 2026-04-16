<?php

namespace App\Enums;

enum TenantUserRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Operator = 'operator';
}
