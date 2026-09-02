<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Tests\Definitions;

use Codejitsu\Ai\Definitions\ModelDefinition;
use Codejitsu\Ai\Exceptions\DefinitionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ModelDefinitionTest extends TestCase
{
    public function testItNormalizesAValidModelRecipe(): void
    {
        $model=ModelDefinition::fromArray($this->valid());
        self::assertSame('codejitsu:latest',$model->destination);
        self::assertSame(['chat','code','tools'],$model->capabilities);
        self::assertSame('var/models',$model->storage);
    }

    #[DataProvider('invalidRecipes')]
    public function testItRejectsUnsafeRecipes(array $changes,string $message): void
    {
        $this->expectException(DefinitionException::class);
        $this->expectExceptionMessage($message);
        ModelDefinition::fromArray([...$this->valid(),...$changes]);
    }

    public static function invalidRecipes(): iterable
    {
        yield 'destination'=>[['destination'=>'bad name'],'destination'];
        yield 'base'=>[['base'=>'$(touch bad)'],'base'];
        yield 'absolute modelfile'=>[['modelfile'=>'/tmp/Modelfile'],'modelfile'];
        yield 'traversal storage'=>[['storage'=>'../models'],'storage'];
    }

    private function valid(): array
    {
        return ['name'=>'codejitsu/local','runtime'=>'ollama','destination'=>'codejitsu:latest','base'=>'hf.co/bartowski/Qwen2.5-Coder-7B-Instruct-abliterated-GGUF:Q5_K_M','size'=>'5.44GB','capabilities'=>['chat','code','tools'],'modelfile'=>'modelfiles/codejitsu/Modelfile','storage'=>'var/models'];
    }
}
