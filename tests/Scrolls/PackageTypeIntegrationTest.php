<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Scrolls\Scroll;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scrolls\Envelope;
use Codejitsu\Scrolls\Stores\Filesystem;
use Codejitsu\Commands\Make;
use Codejitsu\ExecutionContext;
use PHPUnit\Framework\TestCase;

final class PackageTypeIntegrationTest extends TestCase
{
    public function testPackageTypeIsDiscoveredIndexedAndResolvedWithoutACoreEnumCase(): void
    {
        $root = sys_get_temp_dir() . '/codejitsu-world-' . bin2hex(random_bytes(6));
        mkdir($root, 0755, true);
        file_put_contents($root . '/demo.world', "name: demo\nversion: 1.0.0\ntags: [immersive]\ntitle: Shinobi Forge\n");

        try {
            $types = TypeRegistry::builtins();
            $types->register(new TypeDefinition('world', 'worlds', 'world', 'world://', WorldFixture::class));
            $codex = new ScrollCodex(types: $types);
            $codex->load($root, 'ui');

            $world = $codex->resolve('world://demo@ui#1.0.0');
            self::assertInstanceOf(WorldFixture::class, $world);
            self::assertSame('Shinobi Forge', $world->title);
            self::assertSame('world', $codex->query(['type' => 'world'])[0]->type);

            $metadata = new \Codejitsu\Metadata(new \Codejitsu\Identity\Identity(
                \Codejitsu\Enums\Identity\Types::Scroll,
                new \Codejitsu\Identity\Identifier('demo'),
            ));
            $store = new Filesystem($root . '/store', types: $types);
            $store->save('world', 'demo', new Envelope('demo', '1.0.0', 'world', '', $metadata));
            self::assertSame('world', $store->get('world', 'demo')?->scrollType);

            $workingDirectory = getcwd();
            chdir($root);
            try {
                Make::scroll(new ExecutionContext(['world://second'], $codex));
            } finally {
                chdir($workingDirectory);
            }
            self::assertFileExists($root . '/scrolls/worlds/second.world');
        } finally {
            @unlink($root . '/demo.world');
            @unlink($root . '/store/worlds/demo.world');
            @rmdir($root . '/store/worlds');
            @rmdir($root . '/store');
            @unlink($root . '/scrolls/worlds/second.world');
            @rmdir($root . '/scrolls/worlds');
            @rmdir($root . '/scrolls');
            @rmdir($root);
        }
    }
}

final class WorldFixture extends Scroll
{
    public const string TYPE = 'world';
}
