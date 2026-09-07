<?php
declare(strict_types=1);
namespace Codejitsu\Data\Scrolls;
use Codejitsu\Scrolls\Scroll;
use InvalidArgumentException;
final class Entity extends Scroll
{
    public const string TYPE='entity';
    public string $store { get=>$this->attributes['store']; }
    public array $source { get=>$this->attributes['source']; }
    public array $fields { get=>$this->attributes['fields']; }
    public array $sources { get=>$this->attributes['sources']??[]; }
    public array $hooks { get=>$this->attributes['hooks']??[]; }
    public ?string $repository { get=>$this->attributes['repository']??null; }
    public ?string $schema { get=>$this->attributes['schema']??null; }
    public function hydrate(array $data):static
    {
        self::uri($data['store']??null,'store','Entity store');
        if(!is_array($data['source']??null)||$data['source']===[])throw new InvalidArgumentException('Entity source must be a non-empty array.');
        if(!is_array($data['fields']??null)||$data['fields']===[])throw new InvalidArgumentException('Entity fields must be a non-empty array.');
        foreach($data['fields'] as $name=>$field){if(!is_string($name)||trim($name)==='')throw new InvalidArgumentException('Entity field names cannot be empty.');if(is_string($field))self::uri($field,'field',sprintf('Entity field [%s]',$name));elseif(!is_array($field))throw new InvalidArgumentException(sprintf('Entity field [%s] must be a Field URI or inline definition.',$name));}
        if(isset($data['repository']))self::uri($data['repository'],'repository','Entity repository');
        if(isset($data['schema']))self::uri($data['schema'],'schema','Entity schema');
        foreach(['sources','projections','hooks'] as $key)if(isset($data[$key])&&!is_array($data[$key]))throw new InvalidArgumentException(sprintf('Entity %s must be an array.',$key));
        foreach(($data['hooks']??[]) as $phase=>$hooks){if(!is_array($hooks))throw new InvalidArgumentException(sprintf('Entity hook phase [%s] must be an array.',$phase));foreach($hooks as $hook)if(!is_string($hook)||preg_match('/^(strategy|capability):\/\/.+$/',$hook)!==1)throw new InvalidArgumentException(sprintf('Entity hook [%s] must reference a Strategy or Capability.',$phase));}
        return parent::hydrate($data);
    }
    private static function uri(mixed $value,string $scheme,string $label):void{if(!is_string($value)||!str_starts_with($value,$scheme.'://'))throw new InvalidArgumentException(sprintf('%s must be a %s URI.',$label,$scheme));}
}
