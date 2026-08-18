<?php
declare(strict_types=1);

namespace Codejitsu\Enums\Crypto;

use Codejitsu\Contracts\Crypto\Sealer as SealerContract;
use Codejitsu\Contracts\Crypto\Signer as SignerContract;
use Codejitsu\Crypto\Sealers\OpenSSL;
use Codejitsu\Crypto\Signers\Hmac;
use Codejitsu\Enums\Environment;
use Codejitsu\Traits\EnhancedEnum;

enum SignatureAlgorithms: string
{
    use EnhancedEnum;

    case ED25519 = 'ed25519';
    case HMAC_SHA256 = 'hmac-sha256';

    public static function map(): array
    {
        $env = Environment::current();
        
        // Extract environment error policy handlers
        $onUnsignedHandler   = $env->to('$onUnsigned');
        $onInvalidSigHandler = $env->to('$onInvalidSig');
        $onErrorhandler      = $env->to('$onError');

        return [
            'ed25519' => [
                'asymmetric'   => true,
                'class'        => Hmac::class, // Replace with Ed25519 signer class when implemented
                'type'         => 'signer',
                'publicKey'    => KeyType::ED25519_PUB->to('value'),
                'privateKey'   => KeyType::ED25519_PRIV->to('value'),
                'keyValue'     => KeyType::ED25519_PUB->to('keyValue'), // Or generic mapper
                'onUnsigned'   => $onUnsignedHandler,
                'onInvalidSig' => $onInvalidSigHandler,
                '$sealer'      => fn(mixed ...$args): SealerContract => $onErrorhandler(
                    new \RuntimeException('Algorithm [ed25519] does not support sealing.')
                ),
                '$signer'      => fn(mixed ...$args): SignerContract => new Hmac(...$args),
            ],
            'hmac-sha256' => [
                'asymmetric'   => false,
                'class'        => Hmac::class,
                'type'         => 'signer',
                'key'          => KeyType::HMAC->to('value'),
                'keyValue'     => KeyType::HMAC->to('keyValue'),
                'onUnsigned'   => $onUnsignedHandler,
                'onInvalidSig' => $onInvalidSigHandler,
                '$sealer'      => fn(mixed ...$args): SealerContract => $onErrorhandler(
                    new \RuntimeException('Algorithm [hmac-sha256] does not support sealing.')
                ),
                '$signer'      => fn(mixed ...$args): SignerContract => new Hmac(...$args),
            ],
        ];
    }
}