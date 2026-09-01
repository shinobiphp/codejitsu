<?php
declare(strict_types=1);
namespace Codejitsu\Tests\Commands;

use Codejitsu\Commands\Catalogs;
use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scrolls\Types\Catalog;
use PHPUnit\Framework\TestCase;

final class CatalogTest extends TestCase
{
    public function testListsShowsAndSearchesGenericCatalogs(): void
    {
        $types = TypeRegistry::builtins();
        $types->register(new TypeDefinition('catalog', 'catalogs', 'catalog', 'catalog://', Catalog::class));
        $codex = new ScrollCodex(types: $types);
        $codex->registerScroll((new Catalog())->hydrate([
            'name' => 'products',
            'entries' => [
                ['identifier' => 'package://codejitsu/ui#0.1.0', 'kind' => 'package', 'location' => 'composer://codejitsu/ui', 'description' => 'Astro UI runtime'],
                ['identifier' => 'app://shinobi/forge#1.0.0', 'kind' => 'app', 'location' => 'https://example.test/forge', 'description' => 'Shinobi Forge'],
            ],
        ]), 'project-catalogs');
        $context = fn (array $arguments): ExecutionContext => new ExecutionContext($arguments, $codex);

        self::assertStringContainsString('products', Catalogs::list($context([])));
        self::assertStringContainsString('2 entries', Catalogs::list($context([])));
        self::assertStringContainsString('package://codejitsu/ui#0.1.0', Catalogs::show($context(['products'])));
        self::assertStringContainsString('app://shinobi/forge#1.0.0', Catalogs::search($context(['forge'])));
        self::assertSame("No catalog entries found.\n", Catalogs::search($context(['forge', 'package'])));
    }
}
