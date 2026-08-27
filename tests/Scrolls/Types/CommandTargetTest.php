<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls\Types;

use Codejitsu\Scrolls\Types\Command;
use PHPUnit\Framework\TestCase;

final class CommandTargetTest extends TestCase
{
    public function testClassTargetIsInstantiatedWhenExecuted(): void
    {
        $command = new Command();
        $command->hydrate([
            'name' => 'example',
            'target' => CommandTargetFixture::class,
        ]);

        self::assertSame('ok', $command->execute());
    }
}

final class CommandTargetFixture
{
    public function __invoke(): string
    {
        return 'ok';
    }
}
