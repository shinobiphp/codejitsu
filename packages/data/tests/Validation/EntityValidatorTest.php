<?php
declare(strict_types=1);
namespace Codejitsu\Data\Tests\Validation;
use Codejitsu\Data\Scrolls\Entity;use Codejitsu\Data\Validation\EntityValidator;use Codejitsu\Scrolls\ScrollCodex;use Codejitsu\Scrolls\Types\Schema;use InvalidArgumentException;use PHPUnit\Framework\TestCase;
final class EntityValidatorTest extends TestCase
{
 public function testValidatesEntityDataThroughItsSchemaScroll():void{$codex=new ScrollCodex();$codex->registerScroll((new Schema())->hydrate(['name'=>'entities/customer','definition'=>['type'=>'object','required'=>['email'],'properties'=>['email'=>['type'=>'string','format'=>'email']]]]),'project');$entity=(new Entity())->hydrate(['name'=>'customer','store'=>'store://test','source'=>['table'=>'customers'],'schema'=>'schema://entities/customer','fields'=>['email'=>['dataType'=>'string']]]);self::assertSame(['email'=>'valid@example.com'],(new EntityValidator($codex))->validate($entity,['email'=>'valid@example.com']));$this->expectException(InvalidArgumentException::class);(new EntityValidator($codex))->validate($entity,['email'=>42]);}
}
