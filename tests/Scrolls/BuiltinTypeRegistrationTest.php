<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls;

use Codejitsu\Enums\Scrolls\Types;
use Codejitsu\Scrolls\ScrollCodex;
use PHPUnit\Framework\TestCase;

final class BuiltinTypeRegistrationTest extends TestCase
{
    public function testAllBuiltinTypesAreRegisteredWithoutChangingTheirMetadata(): void
    {
        $registry = (new ScrollCodex())->types();

        self::assertCount(count(Types::cases()), $registry->all());
        foreach (Types::cases() as $type) {
            $definition = $registry->get($type->value);
            self::assertSame($type->className(), $definition->scrollClass);
            self::assertSame($type->plural(), $definition->plural);
            self::assertSame($type->extension(), $definition->extension);
            self::assertSame($type->scheme(), $definition->scheme);
            self::assertSame($type, Types::normalize($definition->name));
        }
    }
}
