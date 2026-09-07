<?php
declare(strict_types=1);
namespace Codejitsu\Data\Scrolls;
use Codejitsu\Scrolls\Scroll;
use InvalidArgumentException;
final class Repository extends Scroll
{
    public const string TYPE='repository';
    public string $entity { get=>$this->attributes['entity']; }
    public array $queries { get=>$this->attributes['queries']??[]; }
    public array $dynamic { get=>$this->attributes['dynamic']??[]; }
    public function hydrate(array $data):static
    {
        if(!is_string($data['entity']??null)||!str_starts_with($data['entity'],'entity://'))throw new InvalidArgumentException('Repository entity must be an Entity URI.');
        foreach(['queries','dynamic'] as $key)if(isset($data[$key])&&!is_array($data[$key]))throw new InvalidArgumentException(sprintf('Repository %s must be an array.',$key));
        $limit=$data['dynamic']['maxLimit']??null;if($limit!==null&&(!is_int($limit)||$limit<1))throw new InvalidArgumentException('Repository dynamic maxLimit must be a positive integer.');
        return parent::hydrate($data);
    }
}
