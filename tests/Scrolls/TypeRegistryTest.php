<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scrolls\Types\Context;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TypeRegistryTest extends TestCase
{
    public function testItRegistersAndFindsPackageOwnedTypes(): void
    {
        $world = new TypeDefinition('world', 'worlds', '.world', 'world://', Context::class);
        $registry = (new TypeRegistry())->register($world);

        self::assertTrue($registry->has('WORLD'));
        self::assertSame($world, $registry->get('world'));
        self::assertSame($world, $registry->forExtension('.WORLD'));
        self::assertSame($world, $registry->forScheme('WORLD'));
        self::assertSame([$world], $registry->all());
    }

    public function testItRejectsConflictingDefinitions(): void
    {
        $registry = (new TypeRegistry())->register(
            new TypeDefinition('world', 'worlds', 'world', 'world://', Context::class),
        );

        $this->expectException(InvalidArgumentException::class);
        $registry->register(new TypeDefinition('scene', 'scenes', 'world', 'scene://', Context::class));
    }

    public function testItRejectsInvalidScrollClasses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TypeDefinition('world', 'worlds', 'world', 'world://', self::class);
    }
}
