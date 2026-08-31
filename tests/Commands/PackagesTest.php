<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Commands;

use Codejitsu\Commands\Packages;
use Codejitsu\ExecutionContext;
use PHPUnit\Framework\TestCase;

final class PackagesTest extends TestCase
{
    public function testListIgnoresOrdinaryComposerRequirements(): void
    {
        $directory = sys_get_temp_dir() . '/codejitsu-package-test-' . bin2hex(random_bytes(6));
        mkdir($directory, 0777, true);
        $workingDirectory = getcwd();

        try {
            file_put_contents($directory . '/composer.json', json_encode([
                'require' => [
                    'php' => '>=8.4',
                    'vendor/zeta' => '^2.0',
                ],
                'require-dev' => [
                    'vendor/alpha' => '^1.0',
                ],
            ], JSON_THROW_ON_ERROR));

            chdir($directory);
            $result = Packages::list(new ExecutionContext());

            self::assertSame("No Codejitsu packages are known.\n", $result);
        } finally {
            if ($workingDirectory !== false) {
                chdir($workingDirectory);
            }
            @unlink($directory . '/composer.json');
            @rmdir($directory);
        }
    }

    public function testRejectsInvalidPackageName(): void
    {
        $this->expectException(\RuntimeException::class);

        Packages::install(new ExecutionContext(['not-a-package']));
    }
}
