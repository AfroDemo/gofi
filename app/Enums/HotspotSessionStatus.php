<?php

namespace App\Enums;

enum HotspotSessionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Terminated = 'terminated';
}
