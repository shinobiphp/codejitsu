<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Models;
use Codejitsu\Ai\Definitions\ModelDefinition; use Codejitsu\Contracts\ProcessRunner; use Codejitsu\ProcessResult; use RuntimeException;
final readonly class OllamaModels
{
    public function __construct(private ProcessRunner $process,private string $packageRoot,private string $projectRoot,private ModelfileRenderer $renderer=new ModelfileRenderer()){}
    public function installed(string $name):bool{return $this->show($name)->exitCode===0;}
    public function list():ProcessResult{return $this->process->run(['ollama','list'],$this->projectRoot);}
    public function show(string $name):ProcessResult{return $this->process->run(['ollama','show',$this->name($name)],$this->projectRoot);}
    public function build(ModelDefinition $model,?string $base=null,?string $name=null):ProcessResult
    {
        $source=$this->packageRoot.'/resources/'.$model->modelfile;if(!is_file($source))throw new RuntimeException(sprintf('Modelfile [%s] was not found.',$source));
        $directory=$this->projectRoot.'/var/tmp/codejitsu-ai';if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory))throw new RuntimeException('Unable to create model temporary directory.');
        $temporary=tempnam($directory,'Modelfile-');if($temporary===false)throw new RuntimeException('Unable to create temporary Modelfile.');
        try{if(file_put_contents($temporary,$this->renderer->render((string)file_get_contents($source),$base??$model->base),LOCK_EX)===false)throw new RuntimeException('Unable to render temporary Modelfile.');return $this->process->run(['ollama','create',$this->name($name??$model->destination),'-f',$temporary],$this->projectRoot);}finally{if(is_file($temporary))unlink($temporary);}
    }
    private function name(string $name):string{if(preg_match('/^[a-z0-9][a-z0-9._\/-]*(?::[a-z0-9][a-z0-9._-]*)?$/',$name)!==1)throw new RuntimeException('Invalid Ollama model name.');return $name;}
}
