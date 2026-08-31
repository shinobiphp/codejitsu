<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Discovery\ScrollDiscoverer;
use Codejitsu\Scrolls\Scroll;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Stores\Filesystem;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scrolls\Types\Capability;
use PHPUnit\Framework\TestCase;

final class DiscoveryCompatibilityTest extends TestCase
{
    public function testLegacyDirectoryDiscoveryUsesRegisteredBuiltInAndPackageTypes(): void
    {
        $root = sys_get_temp_dir() . '/codejitsu-discovery-' . bin2hex(random_bytes(6));
        mkdir($root . '/capabilities', 0755, true);
        mkdir($root . '/worlds', 0755, true);
        file_put_contents($root . '/capabilities/hello.capability', "name: hello\ntarget: strlen\n");
        file_put_contents($root . '/worlds/demo.world', "name: demo\ntitle: Demo World\n");

        try {
            $types = TypeRegistry::builtins();
            $types->register(new TypeDefinition('world', 'worlds', 'world', 'world://', LegacyWorldFixture::class));
            $discovered = (new ScrollDiscoverer($root, types: $types))->discover();

            self::assertCount(2, $discovered);
            $codex = new ScrollCodex(types: $types);
            $codex->discover(new Filesystem($root, types: $types), $discovered, 'legacy');

            self::assertCount(2, $codex->query(['source' => 'legacy']));
            self::assertInstanceOf(Capability::class, $codex->resolve('capability://hello@legacy'));
            self::assertInstanceOf(LegacyWorldFixture::class, $codex->resolve('world://demo@legacy'));
        } finally {
            @unlink($root . '/capabilities/hello.capability');
            @unlink($root . '/worlds/demo.world');
            @rmdir($root . '/capabilities');
            @rmdir($root . '/worlds');
            @rmdir($root);
        }
    }
}

final class LegacyWorldFixture extends Scroll
{
    public const string TYPE = 'world';
}
