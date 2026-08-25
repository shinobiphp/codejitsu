<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Enums\Identity\Types as IdentityTypes;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\ExecutionContext;
use Codejitsu\Identity\Identifier;
use Codejitsu\Identity\Identity;
use Codejitsu\Metadata;
use Codejitsu\Scrolls\Envelope;
use Codejitsu\Scrolls\Types\Capability;
use PHPUnit\Framework\TestCase;

final class CapabilitySourceTest extends TestCase
{
    public function testItExecutesInlinePhpSourceWithAutomaticSubstrateDetection(): void
    {
        $envelope = new Envelope(
            'hello',
            '1.0.0',
            ScrollTypes::CAPABILITY,
            '',
            new Metadata(new Identity(IdentityTypes::Scroll, new Identifier('hello'))),
        );

        $capability = Capability::make($envelope, [
            'name' => 'hello',
            'source' => '<?php return "Hello " . ($context->arguments[0] ?? "shinobi");',
        ]);

        self::assertSame('Hello world', $capability(new ExecutionContext(['world'])));
    }
}
