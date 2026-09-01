<?php
declare(strict_types=1);
namespace Codejitsu\Scaffolding;

use Codejitsu\Codecs\Neon;
use RuntimeException;

final readonly class ProjectScaffolder
{
    public function __construct(private string $root, private Neon $codec = new Neon()) {}

    public function catalog(string $name): string
    {
        $name = $this->logicalName($name, 'Catalog');
        $path = $this->root() . '/catalogs/' . $name . '.catalog';
        if (is_file($path)) throw new RuntimeException(sprintf('Catalog [%s] already exists.', $name));
        $this->writeNeon($path, ['name' => $name, 'version' => '1.0.0', 'entries' => []]);
        return $path;
    }

    public function package(string $name, string $description = ''): string
    {
        $name = strtolower(trim($name));
        if (preg_match('/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/', $name) !== 1) {
            throw new RuntimeException('Invalid package name.');
        }
        [, $shortName] = explode('/', $name, 2);
        $path = $this->root() . '/packages/' . $shortName;
        if (file_exists($path)) throw new RuntimeException(sprintf('Package [%s] already exists.', $name));
        $description = trim($description) ?: sprintf('%s Codejitsu package', $name);
        $namespace = implode('', array_map($this->studly(...), explode('/', $name))) . '\\';

        $this->directory($path . '/src');
        $this->directory($path . '/tests');
        $composer = [
            'name' => $name,
            'description' => $description,
            'type' => 'codejitsu-pkg',
            'license' => 'proprietary',
            'extra' => ['codejitsu' => ['manifest' => 'codejitsu.package']],
            'require' => ['php' => '>=8.4', 'codejitsu/core' => 'self.version'],
            'autoload' => ['psr-4' => [$namespace => 'src/']],
            'autoload-dev' => ['psr-4' => [$namespace . 'Tests\\' => 'tests/']],
        ];
        $this->write($path . '/composer.json', json_encode($composer, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        $this->writeNeon($path . '/codejitsu.package', [
            'name' => $name,
            'version' => '0.1.0',
            'description' => $description,
            'capabilities' => ['provides' => [], 'requires' => ['core-runtime']],
        ]);
        $this->write($path . '/src/.gitkeep', '');
        $this->write($path . '/tests/.gitkeep', '');
        $this->addPackageToCatalog($name, $description);
        return $path;
    }

    private function addPackageToCatalog(string $name, string $description): void
    {
        $path = $this->root() . '/catalogs/packages.catalog';
        $data = is_file($path)
            ? $this->codec->decode((string) file_get_contents($path))
            : ['name' => 'packages', 'version' => '1.0.0', 'tags' => ['packages', 'project'], 'entries' => []];
        foreach ($data['entries'] ?? [] as $entry) {
            if (($entry['identifier'] ?? null) === 'package://' . $name . '#0.1.0') {
                throw new RuntimeException(sprintf('Package [%s] is already cataloged.', $name));
            }
        }
        $data['entries'][] = [
            'identifier' => 'package://' . $name . '#0.1.0',
            'kind' => 'package',
            'location' => 'composer://' . $name,
            'version' => '0.1.0',
            'description' => $description,
        ];
        $this->writeNeon($path, $data, overwrite: true);
    }

    private function logicalName(string $name, string $label): string
    {
        if (str_starts_with(trim($name), '/') || str_starts_with(trim($name), '\\')) {
            throw new RuntimeException(sprintf('Invalid %s name.', $label));
        }
        $name = strtolower(trim($name, " /\t\n\r\0\x0B"));
        if (preg_match('/^[a-z0-9][a-z0-9._-]*(?:\/[a-z0-9][a-z0-9._-]*)*$/', $name) !== 1) {
            throw new RuntimeException(sprintf('Invalid %s name.', $label));
        }
        return $name;
    }

    private function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_', '.'], ' ', $value)));
    }

    private function root(): string { return rtrim($this->root, '/\\'); }

    private function directory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Unable to create directory [%s].', $path));
        }
    }

    private function writeNeon(string $path, array $data, bool $overwrite = false): void
    {
        if (!$overwrite && is_file($path)) throw new RuntimeException(sprintf('File [%s] already exists.', $path));
        $this->write($path, $this->codec->encode($data));
    }

    private function write(string $path, string $contents): void
    {
        $this->directory(dirname($path));
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Unable to write file [%s].', $path));
        }
    }
}
