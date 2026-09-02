<?php declare(strict_types=1); namespace Codejitsu\Ai\Models;
use Codejitsu\Ai\Console\ModelSetupIO; use Codejitsu\Ai\Definitions\ModelDefinition; use RuntimeException;
final readonly class ModelSetup
{
    public function __construct(private OllamaModels $models){}
    public function ensure(ModelDefinition $model,ModelSetupIO $io):int
    {
        if($this->models->installed($model->destination))return 0;
        $choice=$io->select('Codejitsu AI model is not installed.',['Download recommended model','Build from an existing GGUF file','Build from another model reference','Skip for now']);
        if($choice==='Skip for now'){$io->write("Model setup skipped. Run model:ensure later.\n");return 0;}
        $base=$model->base;
        if($choice==='Build from an existing GGUF file'){$path=$io->ask('GGUF path: ');$real=realpath($path);if($real===false||!is_file($real)||strtolower(pathinfo($real,PATHINFO_EXTENSION))!=='gguf')throw new RuntimeException('A readable GGUF file is required.');$base=$real;}
        elseif($choice==='Build from another model reference'){$base=$io->ask('Model reference: ');}
        elseif($choice!=='Download recommended model')throw new RuntimeException('Invalid model setup choice.');
        $io->write(sprintf("Model: %s\nSource: %s\nDownload: %s\n",$model->destination,$base,$model->size??'unknown'));
        if(!$io->confirm('Build this model?')){$io->write("Model setup cancelled.\n");return 0;}
        $result=$this->models->build($model,$base);if($result->exitCode!==0)throw new RuntimeException('Ollama model build failed: '.$result->output());
        $test=$this->models->smoke($model->destination);if($test->exitCode!==0||!str_contains($test->stdout,'CODEJITSU_OK'))throw new RuntimeException('Ollama model smoke test failed: '.$test->output());
        $io->write(sprintf("Model [%s] is ready.\n",$model->destination));return 0;
    }
}
