<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Commands;

use Codejitsu\Commands\Scrolls;
use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\ScrollCodex;
use PHPUnit\Framework\TestCase;

final class ScrollsTest extends TestCase
{
    public function testItListsScrollsFromTheExecutionContextCodex(): void
    {
        $codex = new ScrollCodex();

        self::assertSame(
            "No Scrolls are currently registered.\n",
            Scrolls::list(new ExecutionContext([], $codex)),
        );
    }
}
