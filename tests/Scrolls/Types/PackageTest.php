<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls\Types;

use Codejitsu\Scrolls\Types\Package;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PackageTest extends TestCase
{
    public function testNormalizesDeclarativePackageManifest(): void
    {
        $package = (new Package())->hydrate([
            'name' => 'ShinobiPHP/Codejitsu-UI',
            'version' => '0.1.0',
            'description' => 'UI resources',
            'keywords' => ['Astro', 'UI'],
            'homepage' => 'https://shinobi.php',
            'documentation' => 'https://docs.shinobi.php/ui',
            'compatibility' => ['codejitsu' => '^1.0'],
            'capabilities' => ['provides' => ['ui'], 'requires' => ['codex']],
            'dependencies' => ['shinobiphp/codejitsu' => '^1.0'],
            'configuration' => ['schema://ui'],
            'types' => [
                'world' => [
                    'plural' => 'worlds',
                    'extension' => 'world',
                    'scheme' => 'world://',
                    'class' => 'ShinobiPHP\\CodejitsuUi\\Scrolls\\World',
                    'codec' => 'neon',
                ],
            ],
            'sources' => ['ui' => ['path' => 'scrolls']],
        ]);

        self::assertSame('shinobiphp/codejitsu-ui', $package->name);
        self::assertSame(['world' => [
            'plural' => 'worlds',
            'extension' => 'world',
            'scheme' => 'world://',
            'class' => 'ShinobiPHP\\CodejitsuUi\\Scrolls\\World',
            'codec' => 'neon',
        ]], $package->typeDeclarations());
        self::assertSame(['ui' => ['path' => 'scrolls']], $package->sourceDeclarations());
        self::assertSame(['astro', 'ui'], $package->toArray()['keywords']);
    }

    /** @return iterable<string, array{array<string,mixed>,string}> */
    public static function invalidManifests(): iterable
    {
        yield 'package name' => [['name' => 'invalid'], 'name'];
        yield 'type class' => [['name' => 'vendor/pkg', 'types' => ['world' => ['plural' => 'worlds', 'extension' => 'world', 'scheme' => 'world://', 'class' => 'not a class', 'codec' => 'neon']]], 'types.world.class'];
        yield 'codec' => [['name' => 'vendor/pkg', 'types' => ['world' => ['plural' => 'worlds', 'extension' => 'world', 'scheme' => 'world://', 'class' => 'Vendor\\World', 'codec' => 'yaml']]], 'types.world.codec'];
        yield 'source traversal' => [['name' => 'vendor/pkg', 'sources' => ['ui' => ['path' => '../scrolls']]], 'sources.ui.path'];
        yield 'configuration uri' => [['name' => 'vendor/pkg', 'configuration' => ['not-a-uri']], 'configuration.0'];
    }

    #[DataProvider('invalidManifests')]
    public function testRejectsInvalidManifestFields(array $manifest, string $field): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($field);
        (new Package())->hydrate($manifest);
    }
}
