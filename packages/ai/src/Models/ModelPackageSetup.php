<?php declare(strict_types=1); namespace Codejitsu\Ai\Models;
use Codejitsu\Ai\Console\ModelSetupIO;use Codejitsu\Ai\Definitions\ModelDefinition;use Codejitsu\Codecs\Neon;use Codejitsu\Contracts\Packages\PackageSetup;use Codejitsu\Packages\SetupContext;use Codejitsu\ProcessRunner;use RuntimeException;
final class ModelPackageSetup implements PackageSetup
{
    public function run(SetupContext $context):int
    {
        $path=$context->packageRoot.'/resources/models/codejitsu.local.model';if(!is_file($path))throw new RuntimeException('Bundled Codejitsu Model recipe was not found.');
        $model=ModelDefinition::fromArray((new Neon())->decode((string)file_get_contents($path)));$models=new OllamaModels(new ProcessRunner(),$context->packageRoot,$context->projectRoot);
        if(!$context->interactive){$source=$context->environment['CODEJITSU_MODEL_SOURCE']??getenv('CODEJITSU_MODEL_SOURCE')?:null;if(!is_string($source)||trim($source)==='')return 0;$result=$models->build($model,trim($source),$context->environment['CODEJITSU_MODEL_NAME']??getenv('CODEJITSU_MODEL_NAME')?:null);return $result->exitCode;}
        $io=new class($context->io) implements ModelSetupIO { public function __construct(private \Codejitsu\Contracts\Packages\SetupIO $io){}public function select(string $q,array $c):string{return $this->io->select($q,$c);}public function ask(string $q,string $d=''):string{return $this->io->ask($q,$d);}public function confirm(string $q):bool{return $this->io->confirm($q);}public function write(string $m):void{$this->io->write($m);} };
        return(new ModelSetup($models))->ensure($model,$io);
    }
}
