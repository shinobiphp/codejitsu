<?php
declare(strict_types=1);
namespace Codejitsu\Data\Tests\Compiler;
use Codejitsu\Data\Compiler\DataCompiler;
use Codejitsu\Data\Scrolls\Entity;
use Codejitsu\Data\Scrolls\Field;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scrolls\Types\Schema;
use PHPUnit\Framework\TestCase;
final class DataCompilerTest extends TestCase
{
    public function testCompilesAnEntityGraphIntoADtoAndManifest():void
    {
        $types=TypeRegistry::builtins();
        $types->register(new TypeDefinition('entity','entities','entity','entity://',Entity::class));
        $types->register(new TypeDefinition('field','fields','field','field://',Field::class));
        $codex=new ScrollCodex(types:$types);
        $codex->registerScroll((new Schema())->hydrate(['name'=>'fields/email','definition'=>['type'=>'object']]),'project');
        $codex->registerScroll((new Field())->hydrate(['name'=>'customer/email','dataType'=>'string','schema'=>'schema://fields/email']),'project');
        $codex->registerScroll((new Entity())->hydrate(['name'=>'customer','store'=>'store://test','source'=>['table'=>'customers'],'fields'=>['id'=>['dataType'=>'integer'],'email'=>'field://customer/email']]),'project');
        $artifact=(new DataCompiler($codex))->compile('customer','App\\Data');
        self::assertStringContainsString('final readonly class Customer',$artifact->php);
        self::assertStringContainsString('public int $id',$artifact->php);
        self::assertStringContainsString('public string $email',$artifact->php);
        self::assertSame('customers',$artifact->manifest['source']['table']);
        self::assertSame('schema://fields/email',$artifact->manifest['fields']['email']['schema']);
    }
}
