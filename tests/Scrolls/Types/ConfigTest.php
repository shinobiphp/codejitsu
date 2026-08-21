<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls\Types;

use Codejitsu\Scrolls\Types\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testItResolvesNestedValuesAndDefaults(): void
    {
        $config = (new Config())->hydrate([
            'name' => 'app',
            'database' => [
                'host' => 'localhost',
                'port' => 5432,
            ],
        ]);

        self::assertSame('localhost', $config->get('database.host'));
        self::assertSame(5432, $config('database.port'));
        self::assertSame('fallback', $config('database.missing', 'fallback'));
        self::assertSame($config->toArray(), $config());
    }
}
