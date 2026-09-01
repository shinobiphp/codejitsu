<?php
declare(strict_types=1);
namespace Codejitsu\Catalog;

use Codejitsu\Codecs\Neon;
use Codejitsu\Console\Editor;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Catalog;
use Codejitsu\Scrolls\Types\Schema;
use InvalidArgumentException;
use RuntimeException;

final readonly class CatalogManager
{
    public function __construct(
        private string $root,
        private ScrollCodex $codex,
        private Neon $codec = new Neon(),
    ) {}

    public function writable(string $name): bool
    {
        $path = $this->path($name);
        if (!is_file($path)) return false;
        try { $data = $this->decode($path); } catch (InvalidArgumentException) { return false; }
        return ($data['access'] ?? 'writable') !== 'read-only' && is_writable($path);
    }

    /** @param array<string,mixed> $entry */
    public function add(string $catalog, array $entry): void
    {
        $data = $this->writableData($catalog);
        foreach ($data['entries'] ?? [] as $existing) {
            if (($existing['identifier'] ?? null) === ($entry['identifier'] ?? null)) {
                throw new RuntimeException(sprintf('Catalog entry [%s] already exists.', $entry['identifier'] ?? ''));
            }
        }
        $data['entries'][] = $entry;
        $this->validateAndWrite($this->path($catalog), $data);
    }

    public function remove(string $catalog, string $identifier): void
    {
        $data = $this->writableData($catalog);
        $before = count($data['entries'] ?? []);
        $data['entries'] = array_values(array_filter(
            $data['entries'] ?? [],
            static fn (array $entry): bool => ($entry['identifier'] ?? null) !== $identifier,
        ));
        if (count($data['entries']) === $before) throw new RuntimeException(sprintf('Catalog entry [%s] was not found.', $identifier));
        $this->validateAndWrite($this->path($catalog), $data);
    }

    public function editRaw(string $catalog, Editor $editor): void
    {
        $path = $this->path($catalog);
        $data = $this->writableData($catalog);
        $edited = $editor->edit((string) file_get_contents($path));
        $decoded = $this->codec->decode($edited);
        if (($decoded['name'] ?? null) !== ($data['name'] ?? null)) {
            throw new InvalidArgumentException('Raw editing cannot change Catalog identity.');
        }
        $this->validateAndWrite($path, $decoded);
    }

    /** @return array<string,mixed> */
    public function data(string $catalog): array
    {
        return $this->decode($this->path($catalog));
    }

    public function root(): string
    {
        return rtrim($this->root, '/\\');
    }

    /** @return array<string,mixed> */
    private function writableData(string $catalog): array
    {
        if (!$this->writable($catalog)) throw new RuntimeException(sprintf('Catalog [%s] is read-only.', $catalog));
        return $this->data($catalog);
    }

    /** @return array<string,mixed> */
    private function decode(string $path): array
    {
        if (!is_file($path)) throw new RuntimeException(sprintf('Project Catalog [%s] does not exist.', basename($path, '.catalog')));
        return $this->codec->decode((string) file_get_contents($path));
    }

    /** @param array<string,mixed> $data */
    private function validateAndWrite(string $path, array $data): void
    {
        $catalog = (new Catalog())->hydrate($data);
        foreach ($catalog->entries() as $entry) {
            $schemaUri = $catalog->entrySchemas()[$entry['kind']] ?? null;
            if ($schemaUri === null) continue;
            $schema = $this->codex->resolve($schemaUri);
            if (!$schema instanceof Schema) throw new InvalidArgumentException(sprintf('[%s] is not a Schema Scroll.', $schemaUri));
            $schema->validate($entry);
        }
        $temporary = $path . '.tmp.' . bin2hex(random_bytes(5));
        if (file_put_contents($temporary, $this->codec->encode($data), LOCK_EX) === false || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException(sprintf('Unable to atomically write Catalog [%s].', $catalog->name));
        }
    }

    private function path(string $name): string
    {
        $name = strtolower(trim($name));
        if (preg_match('/^[a-z0-9][a-z0-9._-]*$/', $name) !== 1) throw new RuntimeException('Invalid project Catalog name.');
        return rtrim($this->root, '/\\') . '/catalogs/' . $name . '.catalog';
    }
}
