<?php
declare(strict_types=1);
namespace Codejitsu\Data\Repositories;
interface Repository
{
 public function create(array $data):int|string;
 public function find(int|string $id):?array;
 public function query(array $filters=[],array $order=[],?int $limit=null,int $offset=0):array;
 public function update(int|string $id,array $data):bool;
 public function delete(int|string $id):bool;
}
