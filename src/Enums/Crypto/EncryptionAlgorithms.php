<?php

declare(strict_types=1);

namespace Codejitsu\Enums\Crypto;

use Codejitsu\Contracts\Crypto\Sealer as SealerContract;
use Codejitsu\Contracts\Crypto\Signer as SignerContract;
use Codejitsu\Crypto\Sealers\OpenSSL;
use Codejitsu\Crypto\Sealers\Sodium;
use Codejitsu\Enums\Environment;
use Codejitsu\Traits\EnhancedEnum;

enum EncryptionAlgorithms: string
{
    use EnhancedEnum;

    case AES_256_GCM = 'aes-256-gcm';
    case XCHACHA20_POLY1305 = 'xchacha20-poly1305';

    public static function map(): array
    {
        $env = Environment::current();

        $onUnsignedHandler   = $env->to('$onUnsigned');
        $onInvalidSigHandler = $env->to('$onInvalidSig');
        $onErrorHandler      = $env->to('$onError');

        return [
            self::AES_256_GCM->value => [
                'asymmetric'   => false,
                'class'        => OpenSSL::class,
                'type'         => 'sealer',
                'key'          => KeyType::AES->to('value'),
                'keyValue'     => KeyType::AES->to('keyValue'),
                'onUnsigned'   => $onUnsignedHandler,
                'onInvalidSig' => $onInvalidSigHandler,

                '$sealer' => fn (mixed ...$args): SealerContract =>
                    new OpenSSL(...$args),

                '$signer' => fn (mixed ...$args): SignerContract =>
                    $onErrorHandler(
                        new \RuntimeException(
                            'Algorithm [aes-256-gcm] does not support signing.'
                        )
                    ),
            ],

            self::XCHACHA20_POLY1305->value => [
                'asymmetric'   => false,
                'class'        => Sodium::class,
                'type'         => 'sealer',
                'key'          => KeyType::SODIUM->to('value'),
                'keyValue'     => KeyType::SODIUM->to('keyValue'),
                'onUnsigned'   => $onUnsignedHandler,
                'onInvalidSig' => $onInvalidSigHandler,

                '$sealer' => fn (mixed ...$args): SealerContract =>
                    new Sodium(),

                '$signer' => fn (mixed ...$args): SignerContract =>
                    $onErrorHandler(
                        new \RuntimeException(
                            'Algorithm [xchacha20-poly1305] does not support signing.'
                        )
                    ),
            ],
        ];
    }
}