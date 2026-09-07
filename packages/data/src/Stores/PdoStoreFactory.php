<?php
declare(strict_types=1);
namespace Codejitsu\Data\Stores;
use Codejitsu\Data\Scrolls\Store;use InvalidArgumentException;use PDO;
final class PdoStoreFactory
{
 public function dsn(Store $store):string{$c=$this->values($store->connection);return match($store->driver){'sqlite'=>'sqlite:'.($c['database']??throw new InvalidArgumentException('SQLite Store requires connection.database.')),'mysql'=>sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',$c['host']??'127.0.0.1',$c['port']??3306,$c['database']??throw new InvalidArgumentException('MySQL Store requires connection.database.'),$c['charset']??'utf8mb4'),'pgsql'=>sprintf('pgsql:host=%s;port=%s;dbname=%s',$c['host']??'127.0.0.1',$c['port']??5432,$c['database']??throw new InvalidArgumentException('PostgreSQL Store requires connection.database.'))};}
 public function connect(Store $store):PDO{$c=$this->values($store->connection);return new PDO($this->dsn($store),$c['username']??null,$c['password']??null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,...$store->options]);}
 private function values(array $values):array{foreach($values as $key=>$value)if(is_string($value)&&str_starts_with($value,'env://')){$name=substr($value,6);$resolved=$_ENV[$name]??getenv($name);if($resolved===false||$resolved===null)throw new InvalidArgumentException(sprintf('Environment variable [%s] is not set.',$name));$values[$key]=$resolved;}return $values;}
}
