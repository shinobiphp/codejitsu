<?php
declare(strict_types=1);

namespace Codejitsu\Enums\Crypto;

use Codejitsu\Enums\Environment;
use Codejitsu\Traits\EnhancedEnum;

enum KeyType: string
{
    use EnhancedEnum;
    
    case SECRET       = 'secret';
    case PUBLIC       = 'public';
    case PRIVATE      = 'private';
    case AES          = 'aes';
    case SODIUM       = 'sodium';
    case ED25519_PUB  = 'ed25519-pub';
    case ED25519_PRIV = 'ed25519-priv';
    case HMAC         = 'hmac';

    public static function map(): array
    {
        $env = Environment::current();
        $getConfig = $env->to('$cfg');
        $onErrorhandler = $env->to('$onError');

        $resolveKey = function (string $primaryKey, ?string $fallbackKey, string $keyTypeName) use ($getConfig, $onErrorhandler) {
            $key = $getConfig($primaryKey, $fallbackKey ? $getConfig($fallbackKey) : null);
            
            if (empty($key)) {
                return $onErrorhandler(
                    new \RuntimeException("KeyType [{$keyTypeName}] is missing or empty (checked env keys: {$primaryKey}" . ($fallbackKey ? ", {$fallbackKey}" : "") . ").")
                );
            }
            
            return $key;
        };

        return [
            'secret' => [
                'description' => 'Default symmetric secret key',
                'asymmetric'  => false,
                'value'       => $resolveKey('CODEJITSU_SECRET_KEY', null, 'secret'),
                'keyValue'    => function(string $envKey = 'CODEJITSU_SECRET_KEY', mixed $default = null) use ($getConfig, $onErrorhandler) {
                    $val = $getConfig($envKey, $default);
                    if (empty($val)) {
                        $onErrorhandler(new \RuntimeException("Key resolution failed for [secret] using key [{$envKey}]."));
                    }
                    return $val;
                },
            ],
            'public' => [
                'description' => 'Default asymmetric public key',
                'asymmetric'  => true,
                'value'       => $resolveKey('CODEJITSU_PUBLIC_KEY', null, 'public'),
                'keyValue'    => function(string $envKey = 'CODEJITSU_PUBLIC_KEY', mixed $default = null) use ($getConfig, $onErrorhandler) {
                    $val = $getConfig($envKey, $default);
                    if (empty($val)) {
                        $onErrorhandler(new \RuntimeException("Key resolution failed for [public] using key [{$envKey}]."));
                    }
                    return $val;
                },
            ],
            'private' => [
                'description' => 'Default asymmetric private key',
                'asymmetric'  => true,
                'value'       => $resolveKey('CODEJITSU_PRIVATE_KEY', null, 'private'),
                'keyValue'    => function(string $envKey = 'CODEJITSU_PRIVATE_KEY', mixed $default = null) use ($getConfig, $onErrorhandler) {
                    $val = $getConfig($envKey, $default);
                    if (empty($val)) {
                        $onErrorhandler(new \RuntimeException("Key resolution failed for [private] using key [{$envKey}]."));
                    }
                    return $val;
                },
            ],
            'aes' => [
                'description' => 'AES symmetric key',
                'asymmetric'  => false,
                'value'       => $resolveKey('CODEJITSU_AES_KEY', 'CODEJITSU_SECRET_KEY', 'aes'),
                'keyValue'    => function(string $envKey = 'CODEJITSU_AES_KEY', mixed $default = null) use ($getConfig, $onErrorhandler) {
                    $val = $getConfig($envKey, $default);
                    if (empty($val)) {
                        $onErrorhandler(new \RuntimeException("Key resolution failed for [aes] using key [{$envKey}]."));
                    }
                    return $val;
                },
            ],
            'sodium' => [
                'description' => 'Sodium / XChaCha20Poly1305 key',
                'asymmetric'  => false,
                'value'       => $resolveKey('CODEJITSU_SODIUM_KEY', 'CODEJITSU_SECRET_KEY', 'sodium'),
                'keyValue'    => function(string $envKey = 'CODEJITSU_SODIUM_KEY', mixed $default = null) use ($getConfig, $onErrorhandler) {
                    $val = $getConfig($envKey, $default);
                    if (empty($val)) {
                        $onErrorhandler(new \RuntimeException("Key resolution failed for [sodium] using key [{$envKey}]."));
                    }
                    return $val;
                },
            ],
            'ed25519-pub' => [
                'description' => 'Ed25519 public key',
                'asymmetric'  => true,
                'value'       => $resolveKey('CODEJITSU_ED25519_PUBLIC_KEY', 'CODEJITSU_PUBLIC_KEY', 'ed25519-pub'),
                'keyValue'    => function(string $envKey = 'CODEJITSU_ED25519_PUBLIC_KEY', mixed $default = null) use ($getConfig, $onErrorhandler) {
                    $val = $getConfig($envKey, $default);
                    if (empty($val)) {
                        $onErrorhandler(new \RuntimeException("Key resolution failed for [ed25519-pub] using key [{$envKey}]."));
                    }
                    return $val;
                },
            ],
            'ed25519-priv' => [
                'description' => 'Ed25519 private key',
                'asymmetric'  => true,
                'value'       => $resolveKey('CODEJITSU_ED25519_PRIVATE_KEY', 'CODEJITSU_PRIVATE_KEY', 'ed25519-priv'),
                'keyValue'    => function(string $envKey = 'CODEJITSU_ED25519_PRIVATE_KEY', mixed $default = null) use ($getConfig, $onErrorhandler) {
                    $val = $getConfig($envKey, $default);
                    if (empty($val)) {
                        $onErrorhandler(new \RuntimeException("Key resolution failed for [ed25519-priv] using key [{$envKey}]."));
                    }
                    return $val;
                },
            ],
            'hmac' => [
                'description' => 'HMAC key',
                'asymmetric'  => false,
                'value'       => $resolveKey('CODEJITSU_HMAC_KEY', 'CODEJITSU_SECRET_KEY', 'hmac'),
                'keyValue'    => function(string $envKey = 'CODEJITSU_HMAC_KEY', mixed $default = null) use ($getConfig, $onErrorhandler) {
                    $val = $getConfig($envKey, $default);
                    if (empty($val)) {
                        $onErrorhandler(new \RuntimeException("Key resolution failed for [hmac] using key [{$envKey}]."));
                    }
                    return $val;
                },
            ],
        ];
    }
}