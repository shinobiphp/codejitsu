<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Graph\Edge;
use Codejitsu\Scrolls\Types\Context;
use PHPUnit\Framework\TestCase;

final class ScrollGraphTest extends TestCase
{
    public function testNormalizesNamedScrollReferencesIntoGraphEdges(): void
    {
        $scroll = (new Context())->hydrate([
            'name' => 'architecture/codex',
            'references' => [
                '$schema' => 'schema://scroll#1.0.0',
            ],
        ]);

        $edges = $scroll->references();

        self::assertCount(1, $edges);
        self::assertInstanceOf(Edge::class, $edges[0]);
        self::assertSame('schema', $edges[0]->name);
        self::assertSame('reference', $edges[0]->type);
        self::assertSame('uri:schema://scroll#1.0.0', $edges[0]->to);
    }

    public function testSupportsNestedLogicalScrollPaths(): void
    {
        $scroll = (new Context())->hydrate([
            'name' => 'architecture/scrolls',
        ]);

        self::assertSame('architecture/scrolls', $scroll->name);
        self::assertNotNull($scroll->graph()->node('context://architecture/scrolls#1.0.0'));
    }
}
