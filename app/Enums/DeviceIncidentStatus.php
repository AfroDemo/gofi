<?php

namespace App\Enums;

enum DeviceIncidentStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
}
