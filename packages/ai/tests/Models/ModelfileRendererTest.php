<?php declare(strict_types=1); namespace Codejitsu\Ai\Tests\Models;
use Codejitsu\Ai\Models\ModelfileRenderer; use PHPUnit\Framework\TestCase; use RuntimeException;
final class ModelfileRendererTest extends TestCase
{
    public function testItReplacesExactlyOneFromDirective():void { $r=new ModelfileRenderer();self::assertSame("FROM local.gguf\nSYSTEM test\n",$r->render("FROM remote:model\nSYSTEM test\n",'local.gguf')); }
    public function testItRejectsMissingOrMultipleFromDirectives():void { foreach(["SYSTEM test\n","FROM one\nFROM two\n"] as $source){try{(new ModelfileRenderer())->render($source,'base');self::fail('Invalid Modelfile accepted.');}catch(RuntimeException){self::assertTrue(true);}} }
}
