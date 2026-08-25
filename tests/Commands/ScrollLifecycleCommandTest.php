<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Commands;

use Codejitsu\Commands\ScrollLifecycle;
use Codejitsu\Crypto\Key;
use Codejitsu\Envelope;
use Codejitsu\ExecutionContext;
use Codejitsu\Identity\Identifier;
use Codejitsu\Identity\Identity;
use Codejitsu\Enums\Identity\Types as IdentityTypes;
use Codejitsu\Metadata;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Command;
use PHPUnit\Framework\TestCase;

final class ScrollLifecycleCommandTest extends TestCase
{
    private string $key;

    protected function setUp(): void
    {
        $pair = sodium_crypto_sign_keypair();
        $this->key = sodium_crypto_sign_secretkey($pair);
        putenv('CODEJITSU_SCROLL_KEY=' . base64_encode($this->key));
    }

    protected function tearDown(): void
    {
        putenv('CODEJITSU_SCROLL_KEY');
    }

    public function testItSignsAndVerifiesAnExplicitScroll(): void
    {
        $codex = new ScrollCodex();
        $scroll = $this->scroll('hello');
        $codex->registerScroll($scroll);

        $context = new ExecutionContext(['command://hello'], $codex);
        self::assertStringContainsString('Signed 1 Scroll(s).', ScrollLifecycle::sign($context));

        ob_start();
        $result = ScrollLifecycle::verify(new ExecutionContext(['command://hello'], $codex));
        $output = ob_get_clean() ?: '';

        self::assertSame(0, $result);
        self::assertStringContainsString('OK hello', $output);
    }

    public function testAllSignsEveryRegisteredScroll(): void
    {
        $codex = new ScrollCodex();
        $first = $this->scroll('first');
        $second = $this->scroll('second');
        $codex->registerScroll($first);
        $codex->registerScroll($second);

        $result = ScrollLifecycle::sign(new ExecutionContext(['--all'], $codex));

        self::assertStringContainsString('Signed 2 Scroll(s).', $result);
        self::assertNotNull($first->getEnvelope()?->signature);
        self::assertNotNull($second->getEnvelope()?->signature);
    }

    private function scroll(string $name): Command
    {
        $envelope = new Envelope(
            $name,
            '',
            new Metadata(new Identity(IdentityTypes::Scroll, new Identifier($name))),
        );

        return Command::make($envelope, [
            'name' => $name,
            'description' => 'Test command',
        ]);
    }
}
