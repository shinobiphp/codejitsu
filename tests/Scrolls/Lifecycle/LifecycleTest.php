<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls\Lifecycle;

use Codejitsu\Crypto\Key;
use Codejitsu\Crypto\Sealers\Sodium;
use Codejitsu\Crypto\Signers\Ed25519;
use Codejitsu\Enums\Identity\Types as IdentityTypes;
use Codejitsu\Identity\Identifier;
use Codejitsu\Identity\Identity;
use Codejitsu\Metadata;
use Codejitsu\Envelope;
use Codejitsu\Scrolls\Lifecycle\Canonicalizer;
use Codejitsu\Scrolls\Lifecycle\Lifecycle;
use Codejitsu\Scrolls\Types\Command;
use LogicException;
use PHPUnit\Framework\TestCase;

final class LifecycleTest extends TestCase
{
    public function testItSignsVerifiesSealsAndUnseals(): void
    {
        [$scroll, $key] = $this->scrollAndKey();
        $lifecycle = $this->lifecycle();

        $lifecycle->sign($scroll, $key);
        self::assertTrue($scroll->getEnvelope()?->signed);
        self::assertTrue($lifecycle->verify($scroll, $key));

        $lifecycle->seal($scroll, $key);
        self::assertTrue($scroll->getEnvelope()?->sealed);
        self::assertTrue($lifecycle->verify($scroll, $key));

        $lifecycle->unseal($scroll, $key);
        self::assertFalse($scroll->getEnvelope()?->sealed);
        self::assertTrue($scroll->getEnvelope()?->signed);
        self::assertTrue($lifecycle->verify($scroll, $key));
    }

    public function testItRejectsSealingAnUnsignedScroll(): void
    {
        [$scroll, $key] = $this->scrollAndKey();

        $this->expectException(LogicException::class);
        $this->lifecycle()->seal($scroll, $key);
    }

    public function testItRejectsTamperedScrolls(): void
    {
        [$scroll, $key] = $this->scrollAndKey();
        $lifecycle = $this->lifecycle();
        $lifecycle->sign($scroll, $key);

        $scroll->description = 'tampered';

        self::assertFalse($lifecycle->verify($scroll, $key));
    }

    public function testBulkSealingIsAtomicWhenOneScrollFailsVerification(): void
    {
        [$first, $key] = $this->scrollAndKey('first');
        [$second] = $this->scrollAndKey('second');
        $lifecycle = $this->lifecycle();

        $lifecycle->sign($first, $key);
        $lifecycle->sign($second, $key);
        $second->description = 'tampered';

        $this->expectException(LogicException::class);
        try {
            $lifecycle->sealAll([$first, $second], $key);
        } finally {
            self::assertFalse($first->getEnvelope()?->sealed);
            self::assertFalse($second->getEnvelope()?->sealed);
        }
    }

    private function lifecycle(): Lifecycle
    {
        return new Lifecycle(
            new Canonicalizer(),
            new Ed25519(),
            new Sodium(),
        );
    }

    /** @return array{Command, Key} */
    private function scrollAndKey(string $name = 'hello'): array
    {
        $keyPair = sodium_crypto_sign_keypair();
        $key = Key::secret('test', sodium_crypto_sign_secretkey($keyPair));
        $metadata = new Metadata(
            new Identity(IdentityTypes::Scroll, new Identifier($name)),
        );
        $envelope = new Envelope(
            $name,
            '',
            $metadata,
        );
        $scroll = Command::make($envelope, [
            'name' => $name,
            'description' => 'Hello',
        ]);

        return [$scroll, $key];
    }
}
