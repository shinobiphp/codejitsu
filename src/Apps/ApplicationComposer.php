<?php
declare(strict_types=1);
namespace Codejitsu\Apps;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\App;
use InvalidArgumentException;
final readonly class ApplicationComposer {
 public function __construct(private ScrollCodex $codex) {}
 public function compose(string|App $app): EffectiveApplication {
  $scroll=$app instanceof App?$app:$this->resolveApp($app);
  return $this->composeApp($scroll,[]);
 }
 private function composeApp(App $app,array $stack): EffectiveApplication {
  $data=$app->toArray(); $uri='app://'.$app->name;
  if (in_array($uri,$stack,true)) throw new InvalidArgumentException('App inheritance cycle: '.implode(' -> ',[...$stack,$uri]));
  $parent=$data['extends']??null;
  if (!is_string($parent)||trim($parent)==='') return new EffectiveApplication($uri,$data,[$uri],[$uri]);
  $effective=$this->composeApp($this->resolveApp($parent),[...$stack,$uri]);
  return new EffectiveApplication($uri,$this->merge($effective->data,$data),[...$effective->inheritance,$uri],[...$effective->provenance,$uri]);
 }
 private function resolveApp(string $uri): App {
  $scroll=$this->codex->resolve(trim($uri));
  if (!$scroll instanceof App) throw new InvalidArgumentException(sprintf('[%s] does not resolve to an App Scroll.',$uri));
  return $scroll;
 }
 private function merge(array $parent,array $child): array {
  $out=$parent;
  foreach($child as $key=>$value){
   if (in_array($key,['config','schemas','capabilities','dependencies'],true)&&is_array($value)) {
    $base=is_array($out[$key]??null)?$out[$key]:[];
    $out[$key]=array_values(array_unique([...$base,...$value],SORT_REGULAR)); continue;
   }
   if (is_array($value)&&is_array($out[$key]??null)&&!array_is_list($value)&&!array_is_list($out[$key])) {$out[$key]=$this->merge($out[$key],$value); continue;}
   $out[$key]=$value;
  }
  return $out;
 }
}