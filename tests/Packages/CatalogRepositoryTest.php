<?php
declare(strict_types=1);
namespace Codejitsu\Tests\Packages;

use Codejitsu\Catalog\CatalogIndex;
use Codejitsu\Scrolls\Types\Catalog;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use PHPUnit\Framework\TestCase;

final class CatalogRepositoryTest extends TestCase
{
    public function testMergesMultipleCatalogsWithHigherPrioritySourceWinning(): void
    {
        $types = TypeRegistry::builtins();
        $types->register(new TypeDefinition('catalog', 'catalogs', 'catalog', 'catalog://', Catalog::class));
        $codex = new ScrollCodex(types: $types);
        $codex->registerScroll((new Catalog())->hydrate(['name' => 'public', 'entries' => [
            ['identifier' => 'package://vendor/pkg#1.0.0', 'kind' => 'package', 'location' => 'composer://vendor/pkg', 'version' => '^1.0', 'description' => 'Public'],
        ]]), 'public');
        $codex->registerScroll((new Catalog())->hydrate(['name' => 'private', 'entries' => [
            ['identifier' => 'package://vendor/pkg#1.0.0', 'kind' => 'package', 'location' => 'composer://vendor/pkg', 'version' => '^2.0', 'description' => 'Private'],
            ['identifier' => 'package://vendor/secret#1.0.0', 'kind' => 'package', 'location' => 'composer://vendor/secret', 'version' => '^1.0'],
            ['identifier' => 'context://architecture/codex#1.0.0', 'kind' => 'scroll', 'location' => 'https://example.test/codex.ctx'],
        ]]), 'private');

        $entries = (new CatalogIndex($codex))->all('package');
        self::assertSame('^2.0', $entries['package://vendor/pkg#1.0.0']['version']);
        self::assertArrayHasKey('package://vendor/secret#1.0.0', $entries);
        self::assertCount(2, $entries);
    }
}
