<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Types;

use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Scrolls\Scroll;

final class Context extends Scroll
{
    public const ScrollTypes TYPE = ScrollTypes::CONTEXT;

    public function content(): string
    {
        return (string) ($this->attributes['data'] ?? $this->attributes['content'] ?? '');
    }

    public function execute(): string
    {
        return $this->content();
    }
}
