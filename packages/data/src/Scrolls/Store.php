<?php
declare(strict_types=1);
namespace Codejitsu\Data\Scrolls;
use Codejitsu\Scrolls\Scroll;
use InvalidArgumentException;
final class Store extends Scroll
{
    public const string TYPE='store';
    private const array DRIVERS=['sqlite','mysql','pgsql'];
    public string $driver { get=>$this->attributes['driver']; }
    public array $connection { get=>$this->attributes['connection']??[]; }
    public array $options { get=>$this->attributes['options']??[]; }
    public function hydrate(array $data):static
    {
        $driver=strtolower(trim((string)($data['driver']??'')));
        if(!in_array($driver,self::DRIVERS,true))throw new InvalidArgumentException(sprintf('Unsupported Store driver [%s].',$driver));
        $data['driver']=$driver;
        foreach(['connection','options'] as $key)if(isset($data[$key])&&!is_array($data[$key]))throw new InvalidArgumentException(sprintf('Store %s must be an array.',$key));
        return parent::hydrate($data);
    }
}
