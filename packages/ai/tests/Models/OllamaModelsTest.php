<?php declare(strict_types=1); namespace Codejitsu\Ai\Tests\Models;
use Codejitsu\Ai\Definitions\ModelDefinition; use Codejitsu\Ai\Models\OllamaModels; use Codejitsu\Contracts\ProcessRunner; use Codejitsu\ProcessResult; use PHPUnit\Framework\TestCase;
final class OllamaModelsTest extends TestCase
{
    private string $root;
    protected function setUp():void{$this->root=sys_get_temp_dir().'/codejitsu-model-'.bin2hex(random_bytes(4));mkdir($this->root.'/resources/modelfiles/codejitsu',0755,true);file_put_contents($this->root.'/resources/modelfiles/codejitsu/Modelfile',"FROM old:model\nSYSTEM test\n");}
    protected function tearDown():void{$this->remove($this->root);}
    public function testItUsesExactOllamaArgumentsAndBuildsFromRenderedTemporaryFile():void
    {
        $runner=new RecordingRunner([new ProcessResult(0,'model',''),new ProcessResult(0,'created','')]);$models=new OllamaModels($runner,$this->root,$this->root);
        self::assertTrue($models->installed('codejitsu:latest'));
        $result=$models->build($this->model(),'local/model.gguf','codejitsu:test');
        self::assertSame(0,$result->exitCode);self::assertSame(['ollama','show','codejitsu:latest'],$runner->commands[0]);self::assertSame(['ollama','create','codejitsu:test','-f'],array_slice($runner->commands[1],0,4));self::assertStringContainsString("FROM local/model.gguf\n",$runner->modelfile);self::assertFileDoesNotExist($runner->commands[1][4]??'');
    }
    public function testMissingModelReturnsFalse():void{$r=new RecordingRunner([new ProcessResult(1,'','missing')]);self::assertFalse((new OllamaModels($r,$this->root,$this->root))->installed('missing'));}
    private function model():ModelDefinition{return ModelDefinition::fromArray(['name'=>'codejitsu/local','runtime'=>'ollama','destination'=>'codejitsu:latest','base'=>'remote:model','capabilities'=>['tools'],'modelfile'=>'modelfiles/codejitsu/Modelfile','storage'=>'var/models']);}
    private function remove(string $p):void{if(!is_dir($p))return;foreach(scandir($p)?:[] as $e)if(!in_array($e,['.','..'],true)){$c=$p.'/'.$e;is_dir($c)?$this->remove($c):unlink($c);}rmdir($p);}
}
final class RecordingRunner implements ProcessRunner
{
    public array $commands=[];public string $modelfile='';public function __construct(private array $results){}
    public function run(array $command,string $cwd):ProcessResult{$this->commands[]=$command;if(($command[1]??'')==='create'){$path=$command[4]??'';$this->modelfile=is_file($path)?(string)file_get_contents($path):'';}return array_shift($this->results)??new ProcessResult(0,'','');}
}
