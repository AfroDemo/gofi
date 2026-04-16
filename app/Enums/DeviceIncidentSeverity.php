<?php

namespace App\Enums;

enum DeviceIncidentSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
}
