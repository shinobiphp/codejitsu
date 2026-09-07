<?php
declare(strict_types=1);
namespace Codejitsu\Data\Scrolls;
use Codejitsu\Scrolls\Scroll;
use InvalidArgumentException;
final class Field extends Scroll
{
    public const string TYPE = 'field';
    public string $dataType { get => $this->attributes['dataType']; }
    public ?string $schema { get => $this->attributes['schema'] ?? null; }
    public array $strategies { get => $this->attributes['strategies'] ?? []; }
    public array $query { get => $this->attributes['query'] ?? []; }
    public function hydrate(array $data): static
    {
        $type=trim((string)($data['dataType']??''));
        if($type===''||preg_match('/^[a-z][a-z0-9_.-]*$/i',$type)!==1)throw new InvalidArgumentException('Field dataType must be a valid type name.');
        $data['dataType']=strtolower($type);
        if(isset($data['schema']))self::uri($data['schema'],'schema','Field schema');
        foreach(($data['strategies']??[]) as $phase=>$strategies){if(!is_array($strategies))throw new InvalidArgumentException(sprintf('Field strategy phase [%s] must be an array.',$phase));foreach($strategies as $strategy)self::uri($strategy,'strategy','Field strategy');}
        if(isset($data['query'])&&!is_array($data['query']))throw new InvalidArgumentException('Field query must be an array.');
        return parent::hydrate($data);
    }
    private static function uri(mixed $value,string $scheme,string $label):void{if(!is_string($value)||!str_starts_with($value,$scheme.'://'))throw new InvalidArgumentException(sprintf('%s must be a %s URI.',$label,$scheme));}
}
