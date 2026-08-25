<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Commands;

use Codejitsu\Commands\Make;
use Codejitsu\ExecutionContext;
use PHPUnit\Framework\TestCase;

final class MakeTest extends TestCase
{
    private string $directory;
    private string $workingDirectory;

    protected function setUp(): void
    {
        $this->workingDirectory = getcwd();
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'codejitsu-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0755, true);
        chdir($this->directory);
    }

    protected function tearDown(): void
    {
        chdir($this->workingDirectory);
        $this->remove($this->directory);
    }

    public function testItCreatesAScrollFromItsUri(): void
    {
        $result = Make::scroll(new ExecutionContext(['capability://foo/bar']));
        $path = $this->directory . '/scrolls/capabilities/foo_bar.capability';

        self::assertStringContainsString('Created capability Scroll [capability://foo/bar].', $result);
        self::assertFileExists($path);
        self::assertStringContainsString('name: foo/bar', file_get_contents($path));
        self::assertStringContainsString('version: 1.0.0', file_get_contents($path));
    }

    public function testItRejectsDuplicateScrolls(): void
    {
        Make::scroll(new ExecutionContext(['capability://foo/bar']));

        $this->expectException(\RuntimeException::class);
        Make::scroll(new ExecutionContext(['capability://foo/bar']));
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

            $child = $path . DIRECTORY_SEPARATOR . $entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }

        rmdir($path);
    }
}
