<?php
declare(strict_types=1);
namespace Codejitsu\Packages;

use Codejitsu\Codecs\Neon;
use Codejitsu\Scrolls\Types\Package;
use Throwable;

final class PackageCompiler
{
    /** @param list<InstalledPackage> $installed */
    public function compile(array $installed): array
    {
        usort($installed, static fn ($a, $b) => $a->name <=> $b->name);
        $packages = [];
        $claims = ['type' => [], 'extension' => [], 'scheme' => [], 'source' => []];
        foreach ($installed as $item) {
            try {
                $scroll = (new Package())->hydrate((new Neon())->decode((string) file_get_contents($item->manifest)));
            } catch (Throwable $e) {
                throw new PackageException(sprintf('Package [%s] manifest is invalid: %s', $item->name, $e->getMessage()), 0, $e);
            }
            if ($scroll->name !== $item->name) {
                throw new PackageException(sprintf('Package [%s] manifest name is [%s].', $item->name, $scroll->name));
            }
            foreach ($scroll->typeDeclarations() as $name => $type) {
                foreach (['type' => $name, 'extension' => $type['extension'], 'scheme' => $type['scheme']] as $kind => $value) {
                    $this->claim($claims[$kind], (string) $value, $item->name, $kind);
                }
            }
            foreach ($scroll->sourceDeclarations() as $alias => $_) {
                $this->claim($claims['source'], $alias, $item->name, 'source');
            }
            $data = $scroll->toArray();
            $packages[] = [
                'name' => $item->name,
                'version' => $item->version,
                'root' => $item->root,
                'manifest' => $item->manifest,
                'types' => $data['types'] ?? [],
                'sources' => $data['sources'] ?? [],
                'metadata' => array_diff_key($data, array_flip(['type', 'types', 'sources'])),
            ];
        }
        return ['format' => 1, 'fingerprint' => hash('sha256', serialize($packages)), 'packages' => $packages];
    }

    /** @param array<string,string> $claims */
    private function claim(array &$claims, string $value, string $package, string $kind): void
    {
        if (isset($claims[$value])) {
            throw new PackageException(sprintf('Package [%s] %s [%s] conflicts with package [%s].', $package, $kind, $value, $claims[$value]));
        }
        $claims[$value] = $package;
    }
}
