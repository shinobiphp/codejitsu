<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Commands;

use Codejitsu\Commands\ScrollRun;
use Codejitsu\Enums\Identity\Types as IdentityTypes;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\ExecutionContext;
use Codejitsu\Identity\Identifier;
use Codejitsu\Identity\Identity;
use Codejitsu\Metadata;
use Codejitsu\Scrolls\Envelope;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Capability;
use PHPUnit\Framework\TestCase;

final class ScrollRunTest extends TestCase
{
    public function testItExecutesACapabilityResolvedThroughTheCodex(): void
    {
        $codex = new ScrollCodex();
        $envelope = new Envelope(
            'hello',
            '1.0.0',
            ScrollTypes::CAPABILITY,
            '',
            new Metadata(new Identity(IdentityTypes::Scroll, new Identifier('hello'))),
        );

        $capability = Capability::make($envelope, [
            'name' => 'hello',
            'target' => static fn (ExecutionContext $context): string => 'Hello ' . ($context->arguments[0] ?? 'shinobi'),
        ]);
        $codex->registerScroll($capability);

        $result = ScrollRun::run(new ExecutionContext(['capability://hello', 'world'], $codex));

        self::assertSame('Hello world', $result);
    }
}
