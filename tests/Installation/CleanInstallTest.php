<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Installation;

use Codejitsu\ProcessRunner;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;

#[Group('installation')]
#[Large]
final class CleanInstallTest extends TestCase
{
    public function testTrackedCheckoutInstallsAndBootsFromCommittedLock(): void
    {
        $source = dirname(__DIR__, 2);
        $target = sys_get_temp_dir() . '/codejitsu-install-' . bin2hex(random_bytes(6));
        mkdir($target, 0755, true);
        $runner = new ProcessRunner();

        try {
            $listed = $runner->run(['git', 'ls-files', '-z'], $source);
            self::assertSame(0, $listed->exitCode, $listed->output());
            foreach (array_filter(explode("\0", $listed->stdout)) as $relative) {
                $destination = $target . '/' . $relative;
                if (!is_dir(dirname($destination))) {
                    mkdir(dirname($destination), 0755, true);
                }
                copy($source . '/' . $relative, $destination);
            }

            $composer = getenv('COMPOSER_BINARY') ?: 'composer';
            $install = $runner->run([$composer, 'install', '--no-interaction', '--no-progress'], $target);
            self::assertSame(0, $install->exitCode, $install->output());
            $autoload = $runner->run([$composer, 'dump-autoload', '--optimize', '--strict-psr'], $target);
            self::assertSame(0, $autoload->exitCode, $autoload->output());
            $cli = $runner->run([PHP_BINARY, $target . '/bin/codejitsu', 'list'], $target);
            self::assertSame(0, $cli->exitCode, $cli->output());
            self::assertStringContainsString('Available commands:', $cli->output());
        } finally {
            $this->remove($target);
        }
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $child = $path . '/' . $entry;
            is_dir($child) && !is_link($child) ? $this->remove($child) : @unlink($child);
        }
        @rmdir($path);
    }
}
