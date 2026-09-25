<?php
declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls\Types;

use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scrolls\Types\Spec;

use PHPUnit\Framework\TestCase;

use InvalidArgumentException;

final class SpecTest extends TestCase {
    public function testBuiltinRegistryKnowsSpec(): void {
        $type = TypeRegistry::builtins()->forScheme('spec');

        self::assertNotNull($type); self::assertSame('spec',$type->name);
        self::assertEquals($type, TypeRegistry::builtins()->forExtension('.spec'));
    }

    public function testSubjectAndSchemaMustBeNonEmptyStrings(): void {
        $this->expectException(InvalidArgumentException::class);
        (new Spec())->hydrate(['name'=>'bad','subject'=>'']);
    }
}