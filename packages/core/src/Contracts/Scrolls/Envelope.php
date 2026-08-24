<?php

declare(strict_types=1);

namespace Codejitsu\Contracts\Scrolls;

use Codejitsu\Contracts\Envelope as BaseEnvelope;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;

interface Envelope extends BaseEnvelope
{
    public string $version {
        get;
    }

    public ScrollTypes $scrollType {
        get;
    }
}