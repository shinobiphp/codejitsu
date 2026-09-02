<?php declare(strict_types=1); namespace Codejitsu\Packages; use Codejitsu\Contracts\Packages\PackageSetup;use Codejitsu\Contracts\Packages\SetupIO;use RuntimeException;use Throwable;
final class PackageSetupCoordinator
{
    public function run(string $projectRoot,array $compiled,SetupIO $io,bool $interactive,array $environment):void
    {
        foreach($compiled['packages']??[] as $package)foreach($package['setup']??[] as $action=>$definition){
            if(!$interactive&&($definition['interactive']??false)&&($environment['CODEJITSU_AUTO_SETUP']??getenv('CODEJITSU_AUTO_SETUP')?:'')!=='1'){$io->write(sprintf("Optional setup pending for [%s:%s]; run pkg:setup %s.\n",$package['name'],$action,$package['name']));continue;}
            $target=$definition['target'];if(!class_exists($target)){$this->failure($io,$package,$action,$definition,new RuntimeException(sprintf('Setup target [%s] is unavailable.',$target)));continue;}
            $setup=new $target();if(!$setup instanceof PackageSetup){$this->failure($io,$package,$action,$definition,new RuntimeException(sprintf('Setup target [%s] must implement PackageSetup.',$target)));continue;}
            try{$code=$setup->run(new SetupContext($projectRoot,$package['root'],$package['name'],(string)$action,$io,$interactive,$environment));if($code!==0)throw new RuntimeException(sprintf('Setup exited with code %d.',$code));}catch(Throwable $e){$this->failure($io,$package,$action,$definition,$e);}
        }
    }
    private function failure(SetupIO $io,array $package,string|int $action,array $definition,Throwable $error):void{if(!($definition['optional']??false))throw new PackageException(sprintf('Package [%s] setup [%s] failed: %s',$package['name'],$action,$error->getMessage()),0,$error);$io->write(sprintf("Optional setup [%s:%s] failed: %s\n",$package['name'],$action,$error->getMessage()));}
}
