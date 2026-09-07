<?php
declare(strict_types=1);
namespace Codejitsu\Data\Repositories;
use Codejitsu\Data\Scrolls\Entity;use InvalidArgumentException;use PDO;
final readonly class SqlRepository implements Repository
{
 private string $table;private string $primaryKey;private array $fields;private string $quote;
 public function __construct(private PDO $pdo,Entity $entity){$this->table=$this->identifier($entity->source['table']??null);$this->primaryKey=$this->identifier($entity->source['primaryKey']??'id');$this->fields=array_keys($entity->fields);$this->quote=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?'`':'"';}
 public function create(array $data):int|string{$data=$this->writable($data);if($data===[])throw new InvalidArgumentException('Create data cannot be empty.');$columns=array_keys($data);$sql=sprintf('INSERT INTO %s (%s) VALUES (%s)',$this->q($this->table),implode(', ',array_map($this->q(...),$columns)),implode(', ',array_map(fn($c)=>':'.$c,$columns)));$this->pdo->prepare($sql)->execute($data);$id=$data[$this->primaryKey]??$this->pdo->lastInsertId();return ctype_digit((string)$id)?(int)$id:(string)$id;}
 public function find(int|string $id):?array{$stmt=$this->pdo->prepare(sprintf('SELECT * FROM %s WHERE %s = :id LIMIT 1',$this->q($this->table),$this->q($this->primaryKey)));$stmt->execute(['id'=>$id]);$row=$stmt->fetch();return $row===false?null:$row;}
 public function query(array $filters=[],array $order=[],?int $limit=null,int $offset=0):array{$where=[];$params=[];foreach($filters as $field=>$value){$this->field($field);$where[]=$this->q($field).' = :f_'.$field;$params['f_'.$field]=$value;}$parts=['SELECT * FROM '.$this->q($this->table)];if($where!==[])$parts[]='WHERE '.implode(' AND ',$where);if($order!==[]){$orders=[];foreach($order as $field=>$direction){$this->field($field);$direction=strtoupper((string)$direction);if(!in_array($direction,['ASC','DESC'],true))throw new InvalidArgumentException('Order direction must be ASC or DESC.');$orders[]=$this->q($field).' '.$direction;}$parts[]='ORDER BY '.implode(', ',$orders);}if($limit!==null){if($limit<1||$offset<0)throw new InvalidArgumentException('Invalid pagination.');$parts[]='LIMIT '.(int)$limit.' OFFSET '.(int)$offset;}$stmt=$this->pdo->prepare(implode(' ',$parts));$stmt->execute($params);return $stmt->fetchAll();}
 public function update(int|string $id,array $data):bool{$data=$this->writable($data);unset($data[$this->primaryKey]);if($data===[])return false;$sets=[];foreach($data as $field=>$_)$sets[]=$this->q($field).' = :'.$field;$data['_id']=$id;return $this->pdo->prepare(sprintf('UPDATE %s SET %s WHERE %s = :_id',$this->q($this->table),implode(', ',$sets),$this->q($this->primaryKey)))->execute($data);}
 public function delete(int|string $id):bool{$stmt=$this->pdo->prepare(sprintf('DELETE FROM %s WHERE %s = :id',$this->q($this->table),$this->q($this->primaryKey)));$stmt->execute(['id'=>$id]);return $stmt->rowCount()>0;}
 private function writable(array $data):array{foreach(array_keys($data) as $field)$this->field((string)$field);return $data;}
 private function field(string $field):void{if(!in_array($field,$this->fields,true))throw new InvalidArgumentException(sprintf('Unknown Entity field [%s].',$field));}
 private function identifier(mixed $value):string{if(!is_string($value)||preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$value)!==1)throw new InvalidArgumentException('SQL identifiers must be simple names.');return $value;}
 private function q(string $value):string{return $this->quote.$value.$this->quote;}
}
