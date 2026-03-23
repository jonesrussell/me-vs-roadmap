<?php
declare(strict_types=1);

namespace App\Support;

enum ScanStatus: string
{
    case Queued = 'queued';
    case Analyzing = 'analyzing';
    case Complete = 'complete';
    case Failed = 'failed';
}
