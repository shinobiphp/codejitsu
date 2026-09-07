<?php
declare(strict_types=1);
namespace Codejitsu\Data\Validation;
use Codejitsu\Data\Scrolls\Entity;use Codejitsu\Scrolls\ScrollCodex;use Codejitsu\Scrolls\Types\Schema;use LogicException;
final readonly class EntityValidator
{
 public function __construct(private ScrollCodex $codex){}
 public function validate(Entity $entity,array $data):array{if($entity->schema===null)return $data;$schema=$this->codex->resolve($entity->schema);if(!$schema instanceof Schema)throw new LogicException(sprintf('Entity schema [%s] did not resolve to a Schema Scroll.',$entity->schema));$schema->validate($data);return $data;}
}
