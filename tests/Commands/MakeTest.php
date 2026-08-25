<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Commands;

use Codejitsu\Codecs\Neon;
use Codejitsu\Commands\Make;
use Codejitsu\Console\Editor;
use Codejitsu\Console\Questioner;
use Codejitsu\ExecutionContext;
use Codejitsu\Substrate\Php;
use Codejitsu\SubstrateRegistry;
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
        $contents = file_get_contents($path);

        self::assertStringContainsString('Created capability Scroll [capability://foo/bar].', $result);
        self::assertFileExists($path);
        self::assertIsString($contents);
        self::assertStringContainsString('name: foo/bar', $contents);
        self::assertStringContainsString("version: '1.0.0'", $contents);
    }

    public function testItCreatesValidMultilineExecutableSource(): void
    {
        Make::scroll(new ExecutionContext([
            'capability://foo/bar',
            '--source=<?php return "hello";',
        ]));

        $path = $this->directory . '/scrolls/capabilities/foo_bar.capability';
        $contents = file_get_contents($path);

        self::assertIsString($contents);
        self::assertStringContainsString('substrate: auto', $contents);
        self::assertStringContainsString('source: """', $contents);

        $payload = (new Neon())->decode($contents);
        self::assertSame('auto', $payload['substrate']);
        self::assertSame("<?php return \"hello\";\n", $payload['source']);
    }

    public function testInteractiveCreationSelectsTypeSubstrateAndSource(): void
    {
        $registry = new SubstrateRegistry();
        $registry->register('php', new Php());

        $result = Make::interactive(
            null,
            new FakeQuestioner(['capability', 'hello/world', '', 'php']),
            new FakeEditor('<?php return "hello";'),
            $registry,
        );

        $path = $this->directory . '/scrolls/capabilities/hello_world.capability';
        $contents = file_get_contents($path);

        self::assertStringContainsString('Created capability Scroll [capability://hello/world#1.0.0].', $result);
        self::assertFileExists($path);
        self::assertIsString($contents);
        self::assertStringContainsString('substrate: php', $contents);
        self::assertStringContainsString('source: """', $contents);
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

final class FakeEditor implements Editor
{
    public function __construct(private readonly string $contents)
    {
    }

    public function edit(string $initial = ''): string
    {
        return rtrim($this->contents, "\r\n") . "\n";
    }
}

final class FakeQuestioner implements Questioner
{
    /** @param list<string> $answers */
    public function __construct(private array $answers)
    {
    }

    public function ask(string $question, string $default = ''): string
    {
        $answer = array_shift($this->answers);
        return $answer === '' || $answer === null ? $default : $answer;
    }

    public function select(string $question, array $choices, int $default = 0): string
    {
        $answer = array_shift($this->answers);
        return $answer === '' || $answer === null ? $choices[$default] : $answer;
    }
}
