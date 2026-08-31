<?php
declare(strict_types=1);

namespace Codejitsu\Config;

use Codejitsu\Config\MutableConfig;
use Codejitsu\Config\Stores\File as ConfigStore;

class FileConfig extends MutableConfig
{
    protected static function getStoreClass(): string
    {
        return ConfigStore::class;
    }
}
