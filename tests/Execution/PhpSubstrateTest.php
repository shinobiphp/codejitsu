<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Execution;

use Codejitsu\ExecutionContext;
use Codejitsu\Substrate\Php;
use PHPUnit\Framework\TestCase;

final class PhpSubstrateTest extends TestCase
{
    public function testItExecutesInlinePhpSourceInAnExecutionContext(): void
    {
        $substrate = new Php();

        $result = $substrate->execute(
            '<?php return "Hello " . ($context->arguments[0] ?? "shinobi");',
            new ExecutionContext(['world']),
        );

        self::assertSame('Hello world', $result);
    }

    public function testItDetectsPhpFromTheOpeningTag(): void
    {
        self::assertSame('php', Php::detect('<?php return true;'));
    }
}
