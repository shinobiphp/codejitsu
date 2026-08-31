<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Context;
use PHPUnit\Framework\TestCase;

final class ContextQueryTest extends TestCase
{
    public function testItTargetsContextByPathPrefixTagsSourceAndReferences(): void
    {
        $codex = new ScrollCodex();
        $codex->registerScroll(Context::make($this->envelope(), [
            'name' => 'architecture/codex',
            'tags' => ['architecture'],
            'references' => ['scrolls' => 'context://architecture/scrolls@context#1.0.0'],
        ]), 'context');
        $codex->registerScroll(Context::make($this->envelope(), [
            'name' => 'roadmap/current',
            'tags' => ['roadmap'],
        ]), 'context');

        $entries = $codex->query([
            'type' => 'context',
            'path_prefix' => 'architecture',
            'tags' => ['architecture'],
            'source' => 'context',
            'references' => ['context://architecture/scrolls@context#1.0.0'],
        ]);

        self::assertCount(1, $entries);
        self::assertSame('architecture/codex', $entries[0]->name);
        self::assertSame(['context://architecture/scrolls@context#1.0.0'], $entries[0]->references);
    }

    private function envelope(): \Codejitsu\Contracts\Scrolls\Envelope
    {
        return new \Codejitsu\Scrolls\Envelope(
            'fixture',
            '1.0.0',
            \Codejitsu\Enums\Scrolls\Types::CONTEXT,
            '',
            new \Codejitsu\Metadata(new \Codejitsu\Identity\Identity(
                \Codejitsu\Enums\Identity\Types::Scroll,
                new \Codejitsu\Identity\Identifier('fixture'),
            )),
        );
    }
}
