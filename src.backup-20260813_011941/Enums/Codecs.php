<?php
declare(strict_types=1);

namespace Codejitsu\Enums;

use Codejitsu\Contracts\Codec as CodecContract;
use Codejitsu\Codecs\Json as JsonCodec;
use Codejitsu\Codecs\Neon as NeonCodec;
use Codejitsu\Codecs\Php as PhpCodec;
use Codejitsu\Enums\Crypto\SignatureAlgorithms as SignatureAlgorithm;
use Codejitsu\Enums\Crypto\EncryptionAlgorithms as EncryptionAlgorithm;
use Codejitsu\Enums\Environment;
use Codejitsu\Enums\ErrorPolicies;
use Codejitsu\Traits\EnhancedEnum;

enum Codecs: string
{
    use EnhancedEnum;
    
    case JSON = 'json';
    case NEON = 'neon';
    case PHP  = 'php';

    public static function default(): self
    {
        $env = Environment::current();
        $defaultName = $env->to('$cfg')('CODEJITSU_CODEC', 'neon');

        return self::tryFrom($defaultName) ?? self::NEON;
    }

    public static function map(): array
    {
        $env = Environment::current();
        $getConfig = $env->to('$cfg');

        $resolvePolicy = function(string $envKey, ErrorPolicies $default) use ($getConfig): ErrorPolicies {
            $raw = $getConfig($envKey, $default->value);
            return ErrorPolicies::normalize($raw) ?? $default;
        };

        $onUnsignedPolicy   = $resolvePolicy('CODEJITSU_CODEC_ON_UNSIGNED', $env->to('onUnsigned'));
        $onInvalidSigPolicy = $resolvePolicy('CODEJITSU_CODEC_ON_INVALID_SIG', $env->to('onInvalidSig'));

        return [
            'json' => [
                'extension'    => 'json',
                'class'        => JsonCodec::class,
                'signer'       => SignatureAlgorithm::HMAC_SHA256,
                'sealer'       => EncryptionAlgorithm::AES_256_GCM,
                'onUnsigned'   => $onUnsignedPolicy->to('$handle'),
                'onInvalidSig' => $onInvalidSigPolicy->to('$handle'),
                '$signer'      => SignatureAlgorithm::HMAC_SHA256->to('$signer'),
                '$sealer'      => EncryptionAlgorithm::AES_256_GCM->to('$sealer'),
                '$codec'       => fn(mixed ...$args): CodecContract => new JsonCodec(),
            ],
            'neon' => [
                'extension'    => 'neon',
                'class'        => NeonCodec::class,
                'signer'       => SignatureAlgorithm::HMAC_SHA256,
                'sealer'       => EncryptionAlgorithm::AES_256_GCM,
                'onUnsigned'   => $onUnsignedPolicy->to('$handle'),
                'onInvalidSig' => $onInvalidSigPolicy->to('$handle'),
                '$signer'      => SignatureAlgorithm::HMAC_SHA256->to('$signer'),
                '$sealer'      => EncryptionAlgorithm::AES_256_GCM->to('$sealer'),
                '$codec'       => fn(mixed ...$args): CodecContract => new NeonCodec(),
            ],
            'php' => [
                'extension'    => 'php',
                'class'        => PhpCodec::class,
                'signer'       => SignatureAlgorithm::HMAC_SHA256,
                'sealer'       => EncryptionAlgorithm::AES_256_GCM,
                'onUnsigned'   => $onUnsignedPolicy->to('$handle'),
                'onInvalidSig' => $onInvalidSigPolicy->to('$handle'),
                '$signer'      => SignatureAlgorithm::HMAC_SHA256->to('$signer'),
                '$sealer'      => EncryptionAlgorithm::AES_256_GCM->to('$sealer'),
                '$codec'       => fn(mixed ...$args): CodecContract => new PhpCodec(),
            ],
        ];
    }

    public function make(mixed ...$args): CodecContract
    {
        $factory = $this->to('$codec');
        if ($factory instanceof \Closure) {
            return $factory(...$args);
        }

        $class = $this->to('class');
        if (!$class || !class_exists($class)) {
            $env = Environment::current();
            return $env->to('$onError')(
                new \RuntimeException("No valid codec class mapped for codec: {$this->value}")
            );
        }

        return new $class(...$args);
    }
}