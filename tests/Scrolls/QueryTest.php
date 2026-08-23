<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Scrolls\IndexEntry;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Command;
use Codejitsu\Scrolls\Types\Config;
use PHPUnit\Framework\TestCase;

final class QueryTest extends TestCase
{
    public function testItQueriesIndexedMetadataWithoutChangingSourcePrecedence(): void
    {
        $global = (new Config())->hydrate([
            'name' => 'app',
            'settings' => ['mode' => 'global'],
        ]);
        $tenant = (new Config())->hydrate([
            'name' => 'app',
            'settings' => ['mode' => 'tenant'],
        ]);
        $command = (new Command())->hydrate([
            'name' => 'deploy',
        ]);

        $codex = new ScrollCodex();
        $codex->registerSource('global');
        $codex->registerSource('tenant');
        $codex->registerScroll($global, 'global');
        $codex->registerScroll($tenant, 'tenant');
        $codex->registerScroll($command, 'tenant');

        $entries = $codex->query([
            'type' => 'config',
            'source' => 'global',
        ]);

        self::assertCount(1, $entries);
        self::assertInstanceOf(IndexEntry::class, $entries[0]);
        self::assertSame('global', $entries[0]->source);
        self::assertSame('config', $entries[0]->type);
        self::assertSame('app', $entries[0]->name);
        self::assertSame('config://app@global#1.0.0', (string) $entries[0]->uri);
        self::assertSame('global', $entries[0]->attributes['settings']['mode']);
    }

    public function testItCanFilterByTags(): void
    {
        $command = (new Command())->hydrate([
            'name' => 'deploy',
            'tags' => ['release', 'dangerous'],
        ]);

        $codex = new ScrollCodex();
        $codex->registerScroll($command);

        $entries = $codex->query(['tags' => ['release']]);

        self::assertCount(1, $entries);
        self::assertSame(['release', 'dangerous'], $entries[0]->tags);
    }
}
