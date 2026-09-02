<?php declare(strict_types=1); namespace Codejitsu\Tests\Packages;
use Codejitsu\Contracts\Packages\PackageSetup;use Codejitsu\Contracts\Packages\SetupIO;use Codejitsu\Packages\PackageSetupCoordinator;use Codejitsu\Packages\SetupContext;use PHPUnit\Framework\TestCase;
final class PackageSetupCoordinatorTest extends TestCase
{
    public function testItRunsInteractiveDeclaredSetupTargets():void{$io=new FakeSetupIO();$compiled=['packages'=>[['name'=>'acme/pkg','root'=>'/pkg','setup'=>['demo'=>['target'=>DemoSetup::class,'optional'=>true,'interactive'=>true]]]]];(new PackageSetupCoordinator())->run('/project',$compiled,$io,true,[]);self::assertSame('/pkg',DemoSetup::$root);}
    public function testItSkipsInteractiveActionsDuringNormalNonInteractiveInstall():void{DemoSetup::$root='';$io=new FakeSetupIO();$compiled=['packages'=>[['name'=>'acme/pkg','root'=>'/pkg','setup'=>['demo'=>['target'=>DemoSetup::class,'optional'=>true,'interactive'=>true]]]]];(new PackageSetupCoordinator())->run('/project',$compiled,$io,false,[]);self::assertSame('',DemoSetup::$root);self::assertStringContainsString('pkg:setup',$io->messages[0]);}
}
final class DemoSetup implements PackageSetup { public static string $root='';public function run(SetupContext $context):int{self::$root=$context->packageRoot;return 0;} }
final class FakeSetupIO implements SetupIO { public array $messages=[];public function select(string $q,array $c):string{return $c[0];}public function ask(string $q,string $d=''):string{return $d;}public function confirm(string $q):bool{return true;}public function write(string $m):void{$this->messages[]=$m;} }
