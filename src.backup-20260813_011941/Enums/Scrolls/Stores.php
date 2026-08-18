<?php
declare(strict_types=1);

namespace Codejitsu\Enums\Scrolls;

use Codejitsu\Contracts\Scrolls\Store as StoreContract;
use Codejitsu\Scrolls\Stores\Filesystem as FilesystemStore;
use Codejitsu\Enums\Codecs;
use Codejitsu\Enums\Environment;
use Codejitsu\Traits\EnhancedEnum;

enum Stores: string
{
    use EnhancedEnum;
    
    case FILES = 'filesystem';

    public static function map(): array
    {
        $env = Environment::current();
        $getConfig = $env->to('$cfg');
        $codecEnum = Codecs::default();

        return [
            'filesystem' => [
                'class'        => FilesystemStore::class,
                'codec'        => $codecEnum,
                'signer'       => $codecEnum->to('signer'),
                'sealer'       => $codecEnum->to('sealer'),
                'onUnsigned'   => $codecEnum->to('onUnsigned'),
                'onInvalidSig' => $codecEnum->to('onInvalidSig'),
                '$signer'      => $codecEnum->to('$signer'),
                '$sealer'      => $codecEnum->to('$sealer'),
                'path'         => $getConfig('CODEJITSU_STORAGE_PATH', $getConfig('CODEJITSU_PATH', sys_get_temp_dir())),
                '$store'       => fn(mixed ...$args): StoreContract => new FilesystemStore(...$args),
            ],
        ];
    }

    public function make(mixed ...$args): StoreContract
    {
        $factory = $this->to('$store');
        if ($factory instanceof \Closure) {
            return $factory(...$args);
        }

        $class = $this->to('class');
        if (!$class || !class_exists($class)) {
            $env = Environment::current();
            return $env->to('$onError')(
                new \RuntimeException("No valid store class mapped for store: {$this->value}")
            );
        }

        return new $class(...$args);
    }
}