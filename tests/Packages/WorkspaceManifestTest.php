<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Packages;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WorkspaceManifestTest extends TestCase
{
    /** @return iterable<string, array{string, array<string>}> */
    public static function manifests(): iterable
    {
        yield 'core' => ['core', []];
        yield 'discovery' => ['discovery', ['codejitsu/core']];
        yield 'scrolls' => ['scrolls', ['codejitsu/core']];
        yield 'codex' => ['codex', ['codejitsu/core', 'codejitsu/discovery', 'codejitsu/scrolls']];
        yield 'config' => ['config', ['codejitsu/core']];
        yield 'schema' => ['schema', ['codejitsu/core']];
        yield 'console' => ['console', ['codejitsu/core', 'codejitsu/codex']];
        yield 'package' => ['package', ['codejitsu/core', 'codejitsu/scrolls', 'codejitsu/codex']];
        yield 'composer-plugin' => ['composer-plugin', ['codejitsu/package']];
        yield 'context' => ['context', ['codejitsu/core', 'codejitsu/scrolls', 'codejitsu/codex', 'codejitsu/console']];
    }

    /** @param array<string> $internalDependencies */
    #[DataProvider('manifests')]
    public function testWorkspaceManifestIsStrictAndInternallyConsistent(
        string $package,
        array $internalDependencies,
    ): void {
        $directory = dirname(__DIR__, 2) . '/packages/' . $package;
        $manifest = json_decode((string) file_get_contents($directory . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('codejitsu/' . $package, $manifest['name']);
        self::assertSame('proprietary', $manifest['license'] ?? null);
        self::assertArrayNotHasKey('version', $manifest);

        foreach ($internalDependencies as $dependency) {
            self::assertArrayHasKey($dependency, $manifest['require'] ?? []);
        }

        foreach ($manifest['autoload']['psr-4'] ?? [] as $path) {
            self::assertDirectoryExists($directory . '/' . rtrim($path, '/'));
        }
    }
}
