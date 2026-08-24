<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Scrolls\Index;
use Codejitsu\Scrolls\IndexEntry;
use Codejitsu\Uri\Uri;
use PHPUnit\Framework\TestCase;

final class IndexTest extends TestCase
{
    public function testIndexesAndQueriesMetadataWithoutHydratingResources(): void
    {
        $index = new Index();
        $entry = new IndexEntry(
            'config',
            'shinobi',
            '1.0.0',
            'global',
            ['framework', 'runtime'],
            ['environment' => 'production'],
            Uri::make('config://shinobi@global#1.0.0'),
        );

        $index->add($entry);

        self::assertSame($entry, $index->get('config://shinobi@global#1.0.0'));
        self::assertCount(1, $index->query(['type' => 'config']));
        self::assertCount(1, $index->query(['tags' => ['runtime']]));
        self::assertCount(1, $index->query(['attributes' => ['environment' => 'production']]));
    }

    public function testPreservesRequestedSourcePrecedence(): void
    {
        $index = new Index();
        $index->add(new IndexEntry(
            'config',
            'shinobi',
            '1.0.0',
            'global',
            [],
            ['value' => 'global'],
            Uri::make('config://shinobi@global#1.0.0'),
        ));
        $index->add(new IndexEntry(
            'config',
            'shinobi',
            '1.0.0',
            'tenant',
            [],
            ['value' => 'tenant'],
            Uri::make('config://shinobi@tenant#1.0.0'),
        ));

        $results = $index->query(['source' => ['tenant', 'global']]);

        self::assertSame('tenant', $results[0]->source);
        self::assertSame('global', $results[1]->source);
    }

    public function testReplacesEntriesWithTheSameIdentity(): void
    {
        $index = new Index();
        $uri = Uri::make('config://shinobi@global#1.0.0');

        $index->add(new IndexEntry('config', 'shinobi', '1.0.0', 'global', [], ['value' => 1], $uri));
        $index->add(new IndexEntry('config', 'shinobi', '1.0.0', 'global', [], ['value' => 2], $uri));

        self::assertSame(2, $index->get($uri)?->attributes['value']);
        self::assertCount(1, $index);
    }
}
