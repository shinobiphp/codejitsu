<?php
declare(strict_types=1);
namespace Codejitsu\Tests\Scrolls\Types;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scrolls\Types\Spec;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
final class SpecTest extends TestCase {
 public function testBuiltinRegistryKnowsSpec(): void {
  $type=TypeRegistry::builtins()->forScheme('spec');
  self::assertNotNull($type); self::assertSame('spec',$type->name);
  self::assertSame($type,TypeRegistry::builtins()->forExtension('.spec'));
 }
 public function testSubjectAndSchemaMustBeNonEmptyStrings(): void {
  $this->expectException(InvalidArgumentException::class);
  (new Spec())->hydrate(['name'=>'bad','subject'=>'']);
 }
}