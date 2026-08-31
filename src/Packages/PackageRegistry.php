<?php
declare(strict_types=1);
namespace Codejitsu\Packages;

use Codejitsu\Enums\Codecs;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;

final class PackageRegistry
{
    public function apply(array $compiled, ScrollCodex $codex): void
    {
        if (($compiled['format'] ?? null) !== 1 || !is_array($compiled['packages'] ?? null)) {
            throw new PackageException('Compiled package registry is malformed.');
        }
        foreach ($compiled['packages'] as $package) {
            foreach (($package['types'] ?? []) as $name => $type) {
                try {
                    $codex->types()->register(new TypeDefinition(
                        (string) $name, (string) $type['plural'], (string) $type['extension'],
                        (string) $type['scheme'], (string) $type['class'], Codecs::from(strtolower((string) ($type['codec'] ?? 'neon'))),
                        isset($type['schema']) ? (string) $type['schema'] : null,
                    ));
                } catch (\Throwable $e) {
                    throw new PackageException(sprintf('Package [%s] type [%s] cannot be registered: %s', $package['name'] ?? '?', $name, $e->getMessage()), 0, $e);
                }
            }
        }
        foreach ($compiled['packages'] as $package) {
            $root = realpath((string) ($package['root'] ?? ''));
            foreach (($package['sources'] ?? []) as $alias => $source) {
                $path = $root === false ? false : realpath($root . '/' . $source['path']);
                if ($path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
                    throw new PackageException(sprintf('Package [%s] source [%s] is invalid.', $package['name'] ?? '?', $alias));
                }
                $codex->load($path, (string) $alias);
            }
        }
    }
}
