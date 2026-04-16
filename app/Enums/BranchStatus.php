<?php

namespace App\Enums;

enum BranchStatus: string
{
    case Active = 'active';
    case Maintenance = 'maintenance';
    case Inactive = 'inactive';
}
