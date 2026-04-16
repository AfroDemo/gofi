<?php

namespace App\Enums;

enum OperatorFollowUpStatus: string
{
    case NeedsFollowUp = 'needs_follow_up';
    case Resolved = 'resolved';
}
