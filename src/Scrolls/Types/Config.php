<?php
declare(strict_types=1);

namespace Codejitsu\Scrolls\Types;

use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Scrolls\Scroll;

class Config extends Scroll
{
    public const TYPE = ScrollTypes::CONFIG;
}