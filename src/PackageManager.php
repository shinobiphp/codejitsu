<?php

declare(strict_types=1);

namespace Codejitsu;

use Codejitsu\Catalog\CatalogIndex;
use Codejitsu\Contracts\ProcessRunner as ProcessRunnerContract;
use Codejitsu\Contracts\Packages\InstalledPackages;
use Codejitsu\Packages\InstalledPackageDiscovery;
use RuntimeException;

final class PackageManager
{
    public function __construct(
        private readonly ProcessRunnerContract $runner = new ProcessRunner(),
        private readonly ?string $composerBinary = null,
        private readonly InstalledPackages $packages = new InstalledPackageDiscovery(),
        private readonly ?CatalogIndex $catalog = null,
    ) {}

    public function list(string $root): string
    {
        $packages = [];
        foreach ($this->catalog?->all('package') ?? [] as $entry) {
            if (preg_match('~^package://([^#]+)(?:#|$)~', (string) $entry['identifier'], $matches) !== 1) {
                continue;
            }
            $packages[$matches[1]] = [
                'version' => (string) ($entry['version'] ?? 'unknown'),
                'status' => 'available',
            ];
        }

        foreach ($this->packages->all($root) as $package) {
            $packages[$package->name] = ['version' => $package->version, 'status' => 'installed'];
        }

        if ($packages === []) return "No Codejitsu packages are known.\n";

        ksort($packages);
        $output = '';
        foreach ($packages as $name => $package) {
            $output .= sprintf("%-40s %-12s %s\n", $name, $package['version'], $package['status']);
        }

        return $output;
    }

    public function info(string $package, string $root): string
    {
        $result = $this->composer(['show', $package, '--format=json'], $root);
        if ($result['exit'] !== 0) {
            throw new RuntimeException($result['output']);
        }

        return $result['output'];
    }

    public function search(string $query, string $root): string
    {
        $result = $this->composer(['search', $query, '--format=json'], $root);
        if ($result['exit'] !== 0) throw new RuntimeException($result['output']);
        return $result['output'];
    }

    public function install(string $package, string $root): int
    {
        return $this->mutate('require', $package, $root);
    }

    public function remove(string $package, string $root): int
    {
        return $this->mutate('remove', $package, $root);
    }

    public function uninstall(string $package, string $root): int
    {
        return $this->remove($package, $root);
    }

    public function update(?string $package, string $root): int
    {
        $arguments = $package === null
            ? ['update', '--no-interaction', '--no-progress']
            : ['update', $package, '--no-interaction', '--no-progress'];

        $result = $this->composer($arguments, $root);
        if ($result['output'] !== '') {
            echo $result['output'];
        }

        return $result['exit'];
    }

    /** @return array<string, mixed> */
    private function manifest(string $root): array
    {
        if (!is_file($root . '/composer.json')) {
            throw new RuntimeException('composer.json was not found in the project root.');
        }

        $data = json_decode((string) file_get_contents($root . '/composer.json'), true);
        if (!is_array($data)) {
            throw new RuntimeException('composer.json is invalid.');
        }

        return $data;
    }

    private function mutate(string $operation, string $package, string $root): int
    {
        $result = $this->composer([$operation, $package, '--no-interaction', '--no-progress'], $root);
        if ($result['output'] !== '') {
            echo $result['output'];
        }

        return $result['exit'];
    }

    /** @param list<string> $arguments @return array{exit:int,output:string} */
    private function composer(array $arguments, string $root): array
    {
        $binary = $this->composerBinary ?? (getenv('COMPOSER_BINARY') ?: 'composer');
        $result = $this->runner->run(array_merge([$binary], $arguments), $root);

        return [
            'exit' => $result->exitCode,
            'output' => $result->output(),
        ];
    }
}
