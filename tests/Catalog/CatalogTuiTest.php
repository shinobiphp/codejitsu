<?php
declare(strict_types=1);
namespace Codejitsu\Tests\Catalog;

use Codejitsu\Catalog\CatalogActionProvider;
use Codejitsu\Catalog\CatalogIndex;
use Codejitsu\Catalog\CatalogManager;
use Codejitsu\Catalog\CatalogTui;
use Codejitsu\Codecs\Neon;
use Codejitsu\Console\Editor;
use Codejitsu\Console\Questioner;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scrolls\Types\Catalog;
use PHPUnit\Framework\TestCase;

final class CatalogTuiTest extends TestCase
{
    private string $root;
    private CatalogManager $manager;
    private CatalogIndex $index;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/codejitsu-catalog-tui-' . bin2hex(random_bytes(5));
        mkdir($this->root . '/catalogs', 0755, true);
        file_put_contents($this->root . '/catalogs/project.catalog', (new Neon())->encode([
            'name' => 'project', 'version' => '1.0.0', 'entries' => [[
                'identifier' => 'package://codejitsu/ui#0.1.0', 'kind' => 'package', 'location' => 'composer://codejitsu/ui',
            ]],
        ]));
        $types = TypeRegistry::builtins();
        $types->register(new TypeDefinition('catalog', 'catalogs', 'catalog', 'catalog://', Catalog::class));
        $codex = (new ScrollCodex(types: $types))->load($this->root . '/catalogs', 'project-catalogs');
        $this->manager = new CatalogManager($this->root, $codex);
        $this->index = new CatalogIndex($codex);
    }

    protected function tearDown(): void
    {
        @unlink($this->root . '/catalogs/project.catalog');
        @rmdir($this->root . '/catalogs');
        @rmdir($this->root);
    }

    public function testAddsEntryThroughStructuredPrompts(): void
    {
        $tui = new CatalogTui($this->index, $this->manager, []);
        $result = $tui->run(new CatalogQuestioner([
            'project [project-catalogs]', 'Add entry',
            'context://notes/session#1.0.0', 'context', 'project://.context/notes/session.ctx', 'Session notes',
        ]), new NullCatalogEditor());
        self::assertStringContainsString('Added catalog entry', $result);
        $data = (new Neon())->decode((string) file_get_contents($this->root . '/catalogs/project.catalog'));
        self::assertCount(2, $data['entries']);
    }

    public function testBrowsesEntryAndRequiresConfirmationBeforeRemoval(): void
    {
        $identifier = 'package://codejitsu/ui#0.1.0';
        $tui = new CatalogTui($this->index, $this->manager, [new FakeCatalogActions()]);
        $result = $tui->run(new CatalogQuestioner(['project [project-catalogs]', 'Browse entries', $identifier, 'Remove', 'yes']), new NullCatalogEditor());
        self::assertStringContainsString('Removed catalog entry', $result);
        $data = (new Neon())->decode((string) file_get_contents($this->root . '/catalogs/project.catalog'));
        self::assertSame([], $data['entries']);
    }

    public function testExecutesProviderActionForSelectedEntry(): void
    {
        $identifier = 'package://codejitsu/ui#0.1.0';
        $provider = new FakeCatalogActions();
        $tui = new CatalogTui($this->index, $this->manager, [$provider]);
        $result = $tui->run(new CatalogQuestioner(['project [project-catalogs]', 'Browse entries', $identifier, 'Inspect']), new NullCatalogEditor());
        self::assertSame("inspected package://codejitsu/ui#0.1.0\n", $result);
    }
}

final class CatalogQuestioner implements Questioner
{
    public function __construct(private array $answers) {}
    public function ask(string $question, string $default = ''): string { return (string) (array_shift($this->answers) ?? $default); }
    public function select(string $question, array $choices, int $default = 0): string { return (string) (array_shift($this->answers) ?? $choices[$default]); }
}

final class NullCatalogEditor implements Editor
{
    public function edit(string $initial = ''): string { return $initial; }
}

final class FakeCatalogActions implements CatalogActionProvider
{
    public function supports(array $entry): bool { return $entry['kind'] === 'package'; }
    public function actions(array $entry, string $root): array { return ['inspect' => ['label' => 'Inspect', 'consequential' => false]]; }
    public function execute(string $action, array $entry, string $root): string { return 'inspected ' . $entry['identifier'] . "\n"; }
}
