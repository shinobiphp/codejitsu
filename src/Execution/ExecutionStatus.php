<?php
declare(strict_types=1);

namespace Codejitsu\Execution;

use Codejitsu\Traits\EnhancedEnum;

enum ExecutionStatus: string
{
    use EnhancedEnum;

    case PENDING = 'pending';
    case RUNNING = 'running';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case TIMED_OUT = 'timed_out';
}