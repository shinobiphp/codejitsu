<?php
declare(strict_types=1);
namespace Codejitsu\Catalog;

use Codejitsu\Console\Editor;
use Codejitsu\Console\Questioner;
use RuntimeException;

final readonly class CatalogTui
{
    /** @param list<CatalogActionProvider> $providers */
    public function __construct(
        private CatalogIndex $index,
        private CatalogManager $manager,
        private array $providers,
    ) {}

    public function run(Questioner $questioner, Editor $editor): string
    {
        $catalogs = $this->index->catalogs();
        if ($catalogs === []) return "No catalogs found.\n";
        $choices = [];
        $selectedCatalogs = [];
        foreach ($catalogs as $catalog) {
            $label = sprintf('%s [%s]', $catalog['name'], $catalog['source']);
            $choices[] = $label;
            $selectedCatalogs[$label] = $catalog;
        }
        $selectedCatalog = $questioner->select('Catalog', [...$choices, 'Quit']);
        if ($selectedCatalog === 'Quit') return "No changes.\n";
        $catalogRecord = $selectedCatalogs[$selectedCatalog] ?? throw new RuntimeException('Invalid Catalog selection.');
        $catalogName = $catalogRecord['name'];

        $writable = $catalogRecord['source'] === 'project-catalogs' && $this->manager->writable($catalogName);
        $choices = ['Browse entries'];
        if ($writable) $choices = [...$choices, 'Add entry', 'Edit raw'];
        $operation = $questioner->select('Action', [...$choices, 'Quit']);
        if ($operation === 'Quit') return "No changes.\n";
        if ($operation === 'Add entry') return $this->add($catalogName, $questioner);
        if ($operation === 'Edit raw') {
            $this->manager->editRaw($catalogName, $editor);
            return sprintf("Updated Catalog [%s].\n", $catalogName);
        }
        return $this->browse($catalogName, $catalogRecord['uri'], $writable, $questioner);
    }

    private function add(string $catalog, Questioner $questioner): string
    {
        $entry = [
            'identifier' => trim($questioner->ask('Identifier: ')),
            'kind' => strtolower(trim($questioner->ask('Kind: '))),
        ];
        $location = trim($questioner->ask('Location (optional): '));
        $description = trim($questioner->ask('Description (optional): '));
        if ($location !== '') $entry['location'] = $location;
        if ($description !== '') $entry['description'] = $description;
        $this->manager->add($catalog, $entry);
        return sprintf("Added catalog entry [%s].\n", $entry['identifier']);
    }

    private function browse(string $catalogName, string $catalogUri, bool $writable, Questioner $questioner): string
    {
        $catalog = $this->index->catalog($catalogUri);
        $entries = $catalog->entries();
        if ($entries === []) return "Catalog has no entries.\n";
        $identifier = $questioner->select('Entry', array_column($entries, 'identifier'));
        $entry = current(array_filter($entries, static fn (array $item): bool => $item['identifier'] === $identifier));
        if (!is_array($entry)) throw new RuntimeException(sprintf('Catalog entry [%s] was not found.', $identifier));

        $actions = ['view' => ['label' => 'View', 'consequential' => false, 'provider' => null]];
        foreach ($this->providers as $provider) {
            if (!$provider->supports($entry)) continue;
            foreach ($provider->actions($entry, $this->manager->root()) as $id => $definition) {
                $actions[$id] = $definition + ['provider' => $provider];
            }
        }
        if ($writable) $actions['remove'] = ['label' => 'Remove', 'consequential' => true, 'provider' => null];
        $labels = [];
        $actionIds = [];
        foreach ($actions as $actionId => $definition) {
            $labels[] = $definition['label'];
            $actionIds[$definition['label']] = $actionId;
        }
        $selected = $questioner->select('Entry action', $labels);
        $id = $actionIds[$selected] ?? null;
        if (!is_string($id)) throw new RuntimeException('Invalid catalog action selection.');
        if ($actions[$id]['consequential'] && strtolower(trim($questioner->ask('Type yes to confirm: '))) !== 'yes') {
            return "Action cancelled.\n";
        }
        if ($id === 'view') return json_encode($entry, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        if ($id === 'remove') {
            $this->manager->remove($catalogName, $identifier);
            return sprintf("Removed catalog entry [%s].\n", $identifier);
        }
        $provider = $actions[$id]['provider'];
        if (!$provider instanceof CatalogActionProvider) throw new RuntimeException(sprintf('Catalog action [%s] has no provider.', $id));
        return $provider->execute($id, $entry, $this->manager->root());
    }
}
