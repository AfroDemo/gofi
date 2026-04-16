<?php

namespace App\Enums;

enum PackageType: string
{
    case Time = 'time';
    case Data = 'data';
    case Mixed = 'mixed';
}
