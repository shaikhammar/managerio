<?php

namespace App\Domain\Reports\Enums;

enum ReportJobStatus: string
{
    case Queued = 'queued';
    case Completed = 'completed';
    case Failed = 'failed';
}
