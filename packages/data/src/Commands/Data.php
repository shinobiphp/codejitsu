<?php
declare(strict_types=1);
namespace Codejitsu\Data\Commands;
use Codejitsu\Data\Compiler\DataBuilder;use Codejitsu\Data\Compiler\DataCompiler;use Codejitsu\ExecutionContext;use RuntimeException;
final class Data
{
 public static function build(ExecutionContext $context):string{$args=is_array($context->arguments)?array_values($context->arguments):[];$entity=trim((string)($args[0]??''));if($entity==='')throw new RuntimeException('An Entity name or URI is required.');$root=getcwd()?:throw new RuntimeException('Project root unavailable.');$out=$root.'/var/build/data';$namespace='Generated\\Data';foreach(array_slice($args,1) as $arg){if(is_string($arg)&&str_starts_with($arg,'--out-dir='))$out=substr($arg,10);elseif(is_string($arg)&&str_starts_with($arg,'--namespace='))$namespace=substr($arg,12);}$codex=$context->codex??throw new RuntimeException('Data build requires a bound Codex.');$files=(new DataBuilder(new DataCompiler($codex)))->build($entity,$out,$namespace);return "Built ".implode(' and ',$files)."\n";}
}
