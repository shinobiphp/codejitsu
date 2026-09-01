<?php
declare(strict_types=1);
namespace Codejitsu\Tests\Catalog;

use Codejitsu\Catalog\CatalogManager;
use Codejitsu\Codecs\Neon;
use Codejitsu\Console\Editor;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scrolls\Types\Catalog;
use Codejitsu\Scrolls\Types\Schema;
use PHPUnit\Framework\TestCase;

final class CatalogManagerTest extends TestCase
{
    private string $root;
    private ScrollCodex $codex;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/codejitsu-catalog-manager-' . bin2hex(random_bytes(5));
        mkdir($this->root . '/catalogs', 0755, true);
        $types = TypeRegistry::builtins();
        $types->register(new TypeDefinition('catalog', 'catalogs', 'catalog', 'catalog://', Catalog::class));
        $this->codex = new ScrollCodex(types: $types);
        $this->codex->registerScroll((new Schema())->hydrate([
            'name' => 'catalog-entry/package',
            'definition' => [
                'type' => 'object',
                'required' => ['identifier', 'kind', 'location'],
                'properties' => ['kind' => ['const' => 'package']],
            ],
        ]), 'schemas');
        $this->writeCatalog('project', ['entrySchemas' => ['package' => 'schema://catalog-entry/package']]);
        $this->writeCatalog('readonly', ['access' => 'read-only']);
        $this->codex->load($this->root . '/catalogs', 'project-catalogs');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root . '/catalogs/*') ?: [] as $file) @unlink($file);
        @rmdir($this->root . '/catalogs');
        @rmdir($this->root);
    }

    public function testReportsEffectiveProjectAccess(): void
    {
        $manager = new CatalogManager($this->root, $this->codex);
        self::assertTrue($manager->writable('project'));
        self::assertFalse($manager->writable('readonly'));
    }

    public function testAddsAndRemovesValidatedEntriesAtomically(): void
    {
        $manager = new CatalogManager($this->root, $this->codex);
        $entry = ['identifier' => 'package://codejitsu/ui#0.1.0', 'kind' => 'package', 'location' => 'composer://codejitsu/ui'];
        $manager->add('project', $entry);
        $data = (new Neon())->decode((string) file_get_contents($this->root . '/catalogs/project.catalog'));
        self::assertSame($entry['identifier'], $data['entries'][0]['identifier']);
        $manager->remove('project', $entry['identifier']);
        $data = (new Neon())->decode((string) file_get_contents($this->root . '/catalogs/project.catalog'));
        self::assertSame([], $data['entries']);
    }

    public function testRejectsEntryThatFailsKindSchema(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new CatalogManager($this->root, $this->codex))->add('project', [
            'identifier' => 'package://codejitsu/ui#0.1.0', 'kind' => 'package',
        ]);
    }

    public function testRawEditValidatesBeforeReplacingCatalog(): void
    {
        $path = $this->root . '/catalogs/project.catalog';
        $before = (string) file_get_contents($path);
        try {
            (new CatalogManager($this->root, $this->codex))->editRaw('project', new CatalogEditor("entries: broken\n"));
            self::fail('Invalid edit was accepted.');
        } catch (\InvalidArgumentException) {
            self::assertSame($before, file_get_contents($path));
        }
    }

    private function writeCatalog(string $name, array $extra): void
    {
        $data = ['name' => $name, 'version' => '1.0.0', 'entries' => [], ...$extra];
        file_put_contents($this->root . '/catalogs/' . $name . '.catalog', (new Neon())->encode($data));
    }
}

final class CatalogEditor implements Editor
{
    public function __construct(private readonly string $result) {}
    public function edit(string $initial = ''): string { return $this->result; }
}
