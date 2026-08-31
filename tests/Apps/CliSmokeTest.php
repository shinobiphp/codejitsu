<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Apps;

use Codejitsu\ProcessRunner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CliSmokeTest extends TestCase
{
    /** @return iterable<string, array{array<string>, int, string}> */
    public static function commands(): iterable
    {
        yield 'root help' => [[], 0, 'Available commands:'];
        yield 'scroll namespace' => [['scrolls'], 0, 'scrolls:list'];
        yield 'scroll listing' => [['scrolls:list'], 0, 'cmd://'];
        yield 'command execution' => [['hello', 'ninja'], 0, 'Hello, ninja!'];
        yield 'package listing' => [['pkg:list'], 0, 'symfony/console'];
        yield 'invalid command' => [['does:not:exist'], 1, 'no commands defined'];
    }

    /** @param array<string> $arguments */
    #[DataProvider('commands')]
    public function testRealCliEntrypoint(array $arguments, int $exitCode, string $expected): void
    {
        $root = dirname(__DIR__, 2);
        $result = (new ProcessRunner())->run([PHP_BINARY, $root . '/bin/codejitsu', ...$arguments], $root);

        self::assertSame($exitCode, $result->exitCode, $result->output());
        self::assertStringContainsString($expected, $result->output());
    }
}
