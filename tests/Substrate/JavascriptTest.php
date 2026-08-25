<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Substrate;

use Codejitsu\ExecutionContext;
use Codejitsu\Substrate\Javascript;
use PHPUnit\Framework\TestCase;

final class JavascriptTest extends TestCase
{
    public function testExecutesJavascriptSource(): void
    {
        if (!class_exists('V8Js')) {
            self::markTestSkipped('V8Js extension is not installed.');
        }

        self::assertSame(3, (new Javascript())->execute('1 + 2', new ExecutionContext()));
    }
}
