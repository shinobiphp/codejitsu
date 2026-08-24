<?php

declare(strict_types=1);

namespace Codejitsu\Enums;

use Codejitsu\Contracts\Codec as CodecContract;
use Codejitsu\Codecs\Json as JsonCodec;
use Codejitsu\Codecs\Neon as NeonCodec;
use Codejitsu\Codecs\Php as PhpCodec;
use Codejitsu\Enums\Crypto\EncryptionAlgorithms;
use Codejitsu\Enums\Crypto\SignatureAlgorithms;
use Codejitsu\Traits\EnhancedEnum;

enum Codecs: string
{
    use EnhancedEnum;

    case JSON = 'json';
    case NEON = 'neon';
    case PHP = 'php';

    public static function default(): self
    {
        $env = Environment::current();

        $defaultName = strtolower(
            trim(
                (string) $env
                    ->to('$cfg')(
                        'CODEJITSU_CODEC',
                        self::NEON->value,
                    ),
            ),
        );

        return self::tryFrom($defaultName)
            ?? self::NEON;
    }

    public static function map(): array
    {
        $env = Environment::current();
        $getConfig = $env->to('$cfg');

        $resolvePolicy = static function (
            string $envKey,
            ErrorPolicies $default,
        ) use ($getConfig): ErrorPolicies {
            $raw = $getConfig(
                $envKey,
                $default->value,
            );

            return ErrorPolicies::normalize($raw)
                ?? $default;
        };

        $onUnsigned = $resolvePolicy(
            'CODEJITSU_CODEC_ON_UNSIGNED',
            $env->to('onUnsigned'),
        );

        $onInvalidSignature = $resolvePolicy(
            'CODEJITSU_CODEC_ON_INVALID_SIG',
            $env->to('onInvalidSig'),
        );

        $codec = static function (
            string $class,
            SignatureAlgorithms $signer,
            EncryptionAlgorithms $sealer,
        ) use (
            $onUnsigned,
            $onInvalidSignature,
        ): array {
            return [
                'extension' => null,
                'class' => $class,

                'signer' => $signer,
                'sealer' => $sealer,

                'onUnsigned' => $onUnsigned->to('$handle'),
                'onInvalidSig' => $onInvalidSignature->to('$handle'),

                '$signer' => $signer->to('$signer'),
                '$sealer' => $sealer->to('$sealer'),

                '$codec' => static fn(
                    mixed ...$args,
                ): CodecContract => new $class(...$args),
            ];
        };

        return [
            'json' => array_merge(
                $codec(
                    JsonCodec::class,
                    SignatureAlgorithms::HMAC_SHA256,
                    EncryptionAlgorithms::AES_256_GCM,
                ),
                [
                    'extension' => 'json',
                ],
            ),

            'neon' => array_merge(
                $codec(
                    NeonCodec::class,
                    SignatureAlgorithms::HMAC_SHA256,
                    EncryptionAlgorithms::AES_256_GCM,
                ),
                [
                    'extension' => 'neon',
                ],
            ),

            'php' => array_merge(
                $codec(
                    PhpCodec::class,
                    SignatureAlgorithms::HMAC_SHA256,
                    EncryptionAlgorithms::AES_256_GCM,
                ),
                [
                    'extension' => 'php',
                ],
            ),
        ];
    }

    public function make(
        mixed ...$args,
    ): CodecContract {
        $factory = $this->to('$codec');

        if ($factory instanceof \Closure) {
            return $factory(...$args);
        }

        $class = $this->to('class');

        if (
            !is_string($class)
            || !class_exists($class)
        ) {
            return Environment::current()->to('$onError')(
                new \RuntimeException(
                    sprintf(
                        'No valid codec class mapped for codec [%s].',
                        $this->value,
                    ),
                ),
            );
        }

        $codec = new $class(...$args);

        if (!$codec instanceof CodecContract) {
            throw new \UnexpectedValueException(
                sprintf(
                    'Codec class [%s] does not implement CodecContract.',
                    $class,
                ),
            );
        }

        return $codec;
    }
}