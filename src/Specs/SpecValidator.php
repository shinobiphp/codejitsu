<?php
declare(strict_types=1);
namespace Codejitsu\Specs;
use Codejitsu\Scrolls\Scroll;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\App;
use Codejitsu\Scrolls\Types\Capability;
use Codejitsu\Scrolls\Types\Config;
use Codejitsu\Scrolls\Types\Schema;
use Codejitsu\Scrolls\Types\Spec;
use Throwable;
final readonly class SpecValidator {
 public function __construct(private ScrollCodex $codex) {}
 public function validate(Spec $spec, Scroll $subject): ValidationResult {
  $failures=[]; $expected=$spec->subject();
  if ($expected!==null && strtolower($expected)!==strtolower((string)($subject->toArray()['type']??$subject::TYPE->value))) $failures[]=new ValidationFailure('subject',sprintf('Expected [%s].',$expected));
  if (($schemaUri=$spec->schema())!==null) {
   try { $schema=$this->codex->resolve($schemaUri); if(!$schema instanceof Schema) $failures[]=new ValidationFailure('schema','Reference does not resolve to a Schema.'); else $schema->validate($subject->toArray()); }
   catch(Throwable $e){$failures[]=new ValidationFailure('schema',$e->getMessage());}
  }
  if ($subject instanceof App) $this->validateAppReferences($subject,$failures);
  $required=$spec->toArray()['requires']['capabilities']??[];
  if (is_array($required)&&$subject instanceof App) {
   $provided=[];
   foreach(($subject->toArray()['capabilities']??[]) as $uri) {
    try{$cap=$this->codex->resolve((string)$uri); if($cap instanceof Capability)$provided=[...$provided,...$cap->provides()];}catch(Throwable){}
   }
   foreach($required as $requirement){$name=is_array($requirement)?($requirement['provides']??null):$requirement; if(is_string($name)&&!in_array($name,$provided,true))$failures[]=new ValidationFailure('capabilities',sprintf('Missing provider: %s',$name));}
  }
  return new ValidationResult($failures);
 }
 private function validateAppReferences(App $app,array &$failures): void {
  $map=['config'=>Config::class,'schemas'=>Schema::class,'capabilities'=>Capability::class];
  $data=$app->toArray();
  foreach($map as $field=>$class) foreach((array)($data[$field]??[]) as $uri) {
   try{$resolved=$this->codex->resolve((string)$uri); if(!$resolved instanceof $class)$failures[]=new ValidationFailure($field,sprintf('[%s] resolves to wrong Scroll type.',$uri));}
   catch(Throwable $e){$failures[]=new ValidationFailure($field,sprintf('[%s] %s',$uri,$e->getMessage()));}
  }
 }
}