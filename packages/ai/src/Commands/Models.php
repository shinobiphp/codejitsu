<?php declare(strict_types=1); namespace Codejitsu\Ai\Commands;
use Codejitsu\Ai\Console\TerminalModelSetupIO;use Codejitsu\Ai\Definitions\DefinitionLoader;use Codejitsu\Ai\Models\ModelSetup;use Codejitsu\Ai\Models\OllamaModels;use Codejitsu\Catalog\CatalogIndex;use Codejitsu\ExecutionContext;use Codejitsu\ProcessRunner;use RuntimeException;
final class Models
{
    public static function list(ExecutionContext $c):string{return self::models()->list()->output()."\n";}
    public static function search(ExecutionContext $c):string{$q=self::arg($c,0,'A model search query is required.');$rows=(new CatalogIndex(self::codex($c)))->search('model',$q);if($rows===[])return "No model Catalog entries found.\n";return implode("\n",array_map(static fn($r)=>sprintf('%s\t%s',$r['identifier'],$r['description']??''),$rows))."\n";}
    public static function show(ExecutionContext $c):string{$m=(new DefinitionLoader(self::codex($c)))->model(self::arg($c,0,'A Model name or URI is required.'));return json_encode(['name'=>$m->name,'destination'=>$m->destination,'runtime'=>$m->runtime,'base'=>$m->base,'size'=>$m->size,'capabilities'=>$m->capabilities,'installed'=>self::models()->installed($m->destination)],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";}
    public static function build(ExecutionContext $c):string{$m=(new DefinitionLoader(self::codex($c)))->model(self::arg($c,0,'A Model name or URI is required.'));$r=self::models()->build($m);if($r->exitCode!==0)throw new RuntimeException($r->output());return $r->output()."\n";}
    public static function ensure(ExecutionContext $c):int{$m=(new DefinitionLoader(self::codex($c)))->model(self::arg($c,0,'A Model name or URI is required.','codejitsu/local'));return(new ModelSetup(self::models()))->ensure($m,new TerminalModelSetupIO());}
    public static function test(ExecutionContext $c):string{$name=self::arg($c,0,'An Ollama model name is required.','codejitsu:latest');$r=self::models()->smoke($name);if($r->exitCode!==0)throw new RuntimeException($r->output());return $r->output()."\n";}
    private static function models():OllamaModels{$root=getcwd()?:throw new RuntimeException('Project root unavailable.');return new OllamaModels(new ProcessRunner(),dirname(__DIR__,2),$root);}
    private static function codex(ExecutionContext $c):\Codejitsu\Scrolls\ScrollCodex{return $c->codex??throw new RuntimeException('Model commands require a bound Codex.');}
    private static function arg(ExecutionContext $c,int $i,string $error,string $default=''):string{$v=is_array($c->arguments)?$c->arguments[$i]??null:null;if((!is_string($v)||trim($v)==='')&&$default!=='')return $default;if(!is_string($v)||trim($v)==='')throw new RuntimeException($error);return trim($v);}
}
