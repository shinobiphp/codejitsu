<?php

declare(strict_types=1);

namespace Codejitsu\Tests\IO\Translators;

use Codejitsu\IO\Translators\Cli;
use PHPUnit\Framework\TestCase;

final class CliTest extends TestCase
{
    public function testItNormalizesHelpAliases(): void
    {
        foreach (['help', '--help', '-h'] as $alias) {
            $intent = Cli::translate(['./bin/codejitsu', $alias]);

            self::assertSame('', $intent->action);
        }
    }
}
