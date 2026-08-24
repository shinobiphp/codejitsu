<?php

declare(strict_types=1);

namespace Codejitsu;

use Codejitsu\Contracts\Crypto\Signer as SignerContract;
use Codejitsu\Contracts\Crypto\Sealer as SealerContract;
use Codejitsu\Contracts\Codec as CodecContract;
use Codejitsu\Crypto\Signers\HmacSigner;
use Codejitsu\Crypto\Sealers\OpensslSealer;
use Codejitsu\Codecs\Neon;
use Codejitsu\Codecs\Json;
use Codejitsu\Codecs\Php;
use RuntimeException;

class Kernel
{
    protected static ?self $instance = null;
    protected array $config;

    public function __construct(array $config = [])
    {
        $defaultConfig = [
            'default_codec' => 'neon',
            'codecs' => [
                'neon' => Neon::class,
                'json' => Json::class,
                'php'  => Php::class,
            ],
            'signer' => new HmacSigner(
                secretKey: $config['signer_key'] ?? $_ENV['CODEJITSU_SIGNER_KEY'] ?? 'default-insecure-secret'
            ),
            'sealer' => new OpensslSealer(
                masterKey: $config['master_key'] ?? $_ENV['CODEJITSU_MASTER_KEY'] ?? 'default-32-byte-master-key-string!!'
            ),
        ];

        $this->config = array_replace_recursive($defaultConfig, $config);
    }

    /**
     * Boot the kernel via a scroll URI (e.g., config://shinobi, config://archiq)
     */
    public static function boot(string $scrollUri): self
    {
        // Parse the URI schema (e.g., config://shinobi)
        $parsed = parse_url($scrollUri);
        $scheme = $parsed['scheme'] ?? 'config';
        $scrollName = $parsed['host'] ?? $parsed['path'] ?? 'default';

        // In a complete implementation, your store/loader looks up the scroll file
        // based on the scheme and name, reads the envelope, and decodes the configuration payload.
        // For example:
        $loadedConfig = self::loadConfigurationScroll($scheme, $scrollName);

        self::$instance = new self($loadedConfig);

        return self::$instance;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            // Fallback default boot if accessed before explicit boot()
            self::boot('config://default');
        }

        return self::$instance;
    }

    public function getCodec(string $name = null): CodecContract
    {
        $name = $name ?? $this->config['default_codec'];
        $class = $this->config['codecs'][$name] ?? Neon::class;

        return new $class();
    }

    public function getSigner(): SignerContract
    {
        return $this->config['signer'];
    }

    public function getSealer(): SealerContract
    {
        return $this->config['sealer'];
    }

    /**
     * Internal routine to load and parse a configuration scroll file.
     */
    protected static function loadConfigurationScroll(string $scheme, string $name): array
    {
        // Example implementation hook:
        // Locate file paths matching paths like scrolls/{$scheme}/{$name}.neon (or .json / .php)
        // Read via Codec, and return configuration overrides array.
        
        return [
            // Dummy mapping for demonstration; replace with actual Store/Codice lookup
            'target_scroll' => $name,
            'scheme' => $scheme,
        ];
    }
}
