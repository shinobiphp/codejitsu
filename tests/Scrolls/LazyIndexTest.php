<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Scrolls\Scroll;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use PHPUnit\Framework\TestCase;

final class LazyIndexTest extends TestCase
{
    public function testMetadataQueriesDoNotHydrateAndResolutionMemoizesOneResource(): void
    {
        LazyFixtureScroll::$hydrations = 0;
        $root = sys_get_temp_dir() . '/codejitsu-lazy-' . bin2hex(random_bytes(6));
        mkdir($root, 0755, true);
        file_put_contents($root . '/first.lazy', <<<'NEON'
name: architecture/first
version: 1.2.3
tags: [architecture, featured]
title: First
references:
  next: lazy://architecture/second#1.0.0
NEON);
        file_put_contents($root . '/second.lazy', "name: architecture/second\ntags: [architecture]\ntitle: Second\n");

        try {
            $types = TypeRegistry::builtins();
            $types->register(new TypeDefinition('lazy', 'lazies', 'lazy', 'lazy://', LazyFixtureScroll::class));
            $codex = (new ScrollCodex(types: $types))->load($root, 'fixture');

            $entries = $codex->query([
                'type' => 'lazy',
                'source' => 'fixture',
                'path_prefix' => 'architecture',
                'tags' => ['featured'],
                'attributes' => ['title' => 'First'],
                'references' => ['lazy://architecture/second#1.0.0'],
            ]);

            self::assertSame(0, LazyFixtureScroll::$hydrations);
            self::assertCount(1, $entries);
            self::assertSame('architecture/first', $entries[0]->name);
            self::assertSame('1.2.3', $entries[0]->version);
            self::assertSame('first.lazy', $entries[0]->locator);
            self::assertCount(1, $codex->query(['uri' => (string) $entries[0]->uri]));

            $first = $codex->resolve('lazy://architecture/first@fixture#1.2.3');
            self::assertSame(1, LazyFixtureScroll::$hydrations);
            self::assertSame($first, $codex->resolve('lazy://architecture/first@fixture#1.2.3'));
            self::assertSame(1, LazyFixtureScroll::$hydrations);
        } finally {
            @unlink($root . '/first.lazy');
            @unlink($root . '/second.lazy');
            @rmdir($root);
        }
    }
}

final class LazyFixtureScroll extends Scroll
{
    public const string TYPE = 'lazy';
    public static int $hydrations = 0;

    public function hydrate(array $data): static
    {
        self::$hydrations++;
        return parent::hydrate($data);
    }
}
