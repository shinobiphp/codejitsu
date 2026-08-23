<?php

declare(strict_types=1);

namespace Tests\Scrolls;

use Codejitsu\Enums\Scrolls\Types;
use Codejitsu\Scrolls\Types\Context;
use PHPUnit\Framework\TestCase;

final class ContextTest extends TestCase
{
    public function testCreatesMarkdownBackedContextScroll(): void
    {
        $scroll = new Context();
        $scroll->hydrate([
            'name' => 'architecture/codex',
            'tags' => ['architecture', 'agent'],
            'data' => '# Codex\n\nThe resource index.',
        ]);

        self::assertInstanceOf(Context::class, $scroll);
        self::assertSame(Types::CONTEXT, $scroll->type);
        self::assertSame('architecture/codex', $scroll->name);
        self::assertSame(['architecture', 'agent'], $scroll->tags);
        self::assertSame("# Codex\n\nThe resource index.", $scroll->content());
    }
}
