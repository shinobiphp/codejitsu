<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Crypto;

use Codejitsu\Crypto\Signers\Ed25519;
use Codejitsu\Enums\Crypto\SignatureAlgorithms;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class Ed25519Test extends TestCase
{
    public function testItSignsAndVerifiesPayloads(): void
    {
        $keyPair = sodium_crypto_sign_keypair();
        $private = sodium_crypto_sign_secretkey($keyPair);
        $public = sodium_crypto_sign_publickey($keyPair);
        $signer = new Ed25519();

        $signature = $signer->sign('payload', $private);

        self::assertSame(SignatureAlgorithms::ED25519, $signer->algorithm());
        self::assertNotSame('', $signature);
        self::assertTrue($signer->verify('payload', $signature, $public));
        self::assertFalse($signer->verify('tampered', $signature, $public));
    }

    public function testItRejectsInvalidKeys(): void
    {
        $signer = new Ed25519();

        $this->expectException(InvalidArgumentException::class);
        $signer->sign('payload', 'invalid');
    }

    public function testItRejectsMalformedSignatures(): void
    {
        $keyPair = sodium_crypto_sign_keypair();
        $public = sodium_crypto_sign_publickey($keyPair);
        $signer = new Ed25519();

        self::assertFalse($signer->verify('payload', 'invalid', $public));
    }
}
