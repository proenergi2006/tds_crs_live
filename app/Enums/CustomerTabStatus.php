<?php

namespace App\Enums;

enum CustomerTabStatus: string
{
    case Empty = 'empty';
    case InProgress = 'in_progress';
    case Complete = 'complete';
    case Rejected = 'rejected';
}
