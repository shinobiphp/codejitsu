<?php
declare(strict_types=1);

namespace Codejitsu\Enums;

use Codejitsu\Enums\Scrolls\Stores;
use Codejitsu\Enums\Crypto\EncryptionAlgorithms as EncryptionAlgorithm;
use Codejitsu\Enums\Crypto\SignatureAlgorithms as SignatureAlgorithm;
use Codejitsu\Traits\EnhancedEnum;
use Throwable;

enum Environment: string
{
    use EnhancedEnum;
    
    case LOCAL       = 'local';
    case DEVELOPMENT = 'development';
    case TESTING     = 'testing';
    case STAGING     = 'staging';
    case PRODUCTION  = 'production';

    /**
     * Get the current active environment based on CODEJITSU_ENV or default to production.
     */
    public static function current(): self
    {
        $envName = $_ENV['CODEJITSU_ENV'] ?? getenv('CODEJITSU_ENV') ?: self::PRODUCTION->value;
        return self::normalize($envName) ?? self::PRODUCTION;
    }

    /**
     * Trigger an error using the current environment's onError policy handler.
     */
    public static function error(Throwable|string $exception): mixed
    {
        if (is_string($exception)) {
            $exception = new \RuntimeException($exception);
        }

        // Fetch the ErrorPolicies enum and invoke its $handle closure
        $policy = self::current()->to('onError');
        return $policy->to('$handle')($exception);
    }

    public static function map(): array
    {
        $resolveEnv = fn(string $key, mixed $default): mixed => 
            $_ENV[$key] ?? (getenv($key) !== false ? getenv($key) : $default);

        $buildConfig = function(
            string $envName, 
            Stores $defaultStore, 
            Codecs $defaultCodec, 
            SignatureAlgorithm $defaultSigner, 
            EncryptionAlgorithm $defaultSealer, 
            ErrorPolicies $defaultOnUnsigned, 
            ErrorPolicies $defaultOnInvalidSig, 
            ErrorPolicies $defaultOnError,
            mixed $defaultSecretKey = null,
            mixed $defaultPublicKey = null,
            mixed $defaultPrivateKey = null,
            mixed $defaultAesKey = null,
            mixed $defaultSodiumKey = null,
            mixed $defaultEd25519Pub = null,
            mixed $defaultEd25519Priv = null,
            mixed $defaultHmacKey = null
        ) use ($resolveEnv) {
            
            $onUnsigned   = ErrorPolicies::normalize($resolveEnv('CODEJITSU_ON_UNSIGNED', $defaultOnUnsigned->value)) ?? $defaultOnUnsigned;
            $onInvalidSig = ErrorPolicies::normalize($resolveEnv('CODEJITSU_ON_INVALID_SIG', $defaultOnInvalidSig->value)) ?? $defaultOnInvalidSig;
            $onError      = ErrorPolicies::normalize($resolveEnv('CODEJITSU_ON_ERROR', $defaultOnError->value)) ?? $defaultOnError;

            return [
                'store'                  => Stores::normalize($resolveEnv('CODEJITSU_STORE', $defaultStore->value)) ?? $defaultStore,
                'codec'                  => Codecs::normalize($resolveEnv('CODEJITSU_CODEC', $defaultCodec->value)) ?? $defaultCodec,
                'signer'                 => SignatureAlgorithm::normalize($resolveEnv('CODEJITSU_SIGNER', $defaultSigner->value)) ?? $defaultSigner,
                'sealer'                 => EncryptionAlgorithm::normalize($resolveEnv('CODEJITSU_SEALER', $defaultSealer->value)) ?? $defaultSealer,
                
                // Store the Policy objects themselves
                'onUnsigned'             => $onUnsigned,
                'onInvalidSig'           => $onInvalidSig,
                'onError'                => $onError,

                // Directly expose the $handle closures for direct invocation if desired
                '$onUnsigned'            => $onUnsigned->to('$handle'),
                '$onInvalidSig'          => $onInvalidSig->to('$handle'),
                '$onError'               => $onError->to('$handle'),
                
                // Cryptographic Keys Mapping
                'CODEJITSU_SECRET_KEY'   => $resolveEnv('CODEJITSU_SECRET_KEY', $defaultSecretKey),
                'CODEJITSU_PUBLIC_KEY'   => $resolveEnv('CODEJITSU_PUBLIC_KEY', $defaultPublicKey),
                'CODEJITSU_PRIVATE_KEY'  => $resolveEnv('CODEJITSU_PRIVATE_KEY', $defaultPrivateKey),
                'CODEJITSU_AES_KEY'      => $resolveEnv('CODEJITSU_AES_KEY', $defaultAesKey),
                'CODEJITSU_SODIUM_KEY'   => $resolveEnv('CODEJITSU_SODIUM_KEY', $defaultSodiumKey),
                'CODEJITSU_ED25519_PUB'  => $resolveEnv('CODEJITSU_ED25519_PUB', $defaultEd25519Pub),
                'CODEJITSU_ED25519_PRIV' => $resolveEnv('CODEJITSU_ED25519_PRIV', $defaultEd25519Priv),
                'CODEJITSU_HMAC_KEY'     => $resolveEnv('CODEJITSU_HMAC_KEY', $defaultHmacKey),

                '$cfg'                   => fn(string $key, mixed $default = null): mixed => $resolveEnv($key, $default),
            ];
        };

        return [
            'local' => $buildConfig(
                'local', 
                Stores::FILES, 
                Codecs::NEON, 
                SignatureAlgorithm::HMAC_SHA256, 
                EncryptionAlgorithm::AES_256_GCM, 
                ErrorPolicies::IGNORE, 
                ErrorPolicies::WARN, 
                ErrorPolicies::WARN,
                'local-default-secret-key'
            ),
            'development' => $buildConfig(
                'development', 
                Stores::FILES, 
                Codecs::NEON, 
                SignatureAlgorithm::HMAC_SHA256, 
                EncryptionAlgorithm::AES_256_GCM, 
                ErrorPolicies::IGNORE, 
                ErrorPolicies::ERROR, 
                ErrorPolicies::ERROR
            ),
            'testing' => $buildConfig(
                'testing', 
                Stores::FILES, 
                Codecs::NEON, 
                SignatureAlgorithm::HMAC_SHA256, 
                EncryptionAlgorithm::AES_256_GCM, 
                ErrorPolicies::IGNORE, 
                ErrorPolicies::ERROR, 
                ErrorPolicies::ERROR
            ),
            'staging' => $buildConfig(
                'staging', 
                Stores::FILES, 
                Codecs::NEON, 
                SignatureAlgorithm::HMAC_SHA256, 
                EncryptionAlgorithm::AES_256_GCM, 
                ErrorPolicies::ERROR, 
                ErrorPolicies::ERROR, 
                ErrorPolicies::ERROR
            ),
            'production' => $buildConfig(
                'production', 
                Stores::FILES, 
                Codecs::NEON, 
                SignatureAlgorithm::HMAC_SHA256, 
                EncryptionAlgorithm::AES_256_GCM, 
                ErrorPolicies::ERROR, 
                ErrorPolicies::ERROR, 
                ErrorPolicies::ERROR
            ),
        ];
    }
}