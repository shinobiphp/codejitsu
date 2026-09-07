<?php
declare(strict_types=1);
namespace Codejitsu\Data\Compiler;
use RuntimeException;
final readonly class DataBuilder
{
 public function __construct(private DataCompiler $compiler){}
 public function build(string $entity,string $outputDirectory,string $namespace='Generated\\Data'):array{$artifact=$this->compiler->compile($entity,$namespace);if(!is_dir($outputDirectory)&&!mkdir($outputDirectory,0775,true)&&!is_dir($outputDirectory))throw new RuntimeException(sprintf('Unable to create Data build directory [%s].',$outputDirectory));$php=$outputDirectory.'/'.$artifact->className.'.php';$manifest=$outputDirectory.'/'.$artifact->className.'.manifest.json';if(file_put_contents($php,$artifact->php)===false||file_put_contents($manifest,json_encode($artifact->manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n")===false)throw new RuntimeException('Unable to write compiled Data artifacts.');return [$php,$manifest];}
}
