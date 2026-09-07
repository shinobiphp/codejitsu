<?php
declare(strict_types=1);
namespace Codejitsu\Data\Tests\Repositories;
use Codejitsu\Data\Repositories\SqlRepository;use Codejitsu\Data\Scrolls\Entity;use PDO;use PHPUnit\Framework\TestCase;
final class SqlRepositoryTest extends TestCase
{
 public function testCreatesFindsFiltersAndOrdersRows():void{$pdo=new PDO('sqlite::memory:');$pdo->exec('CREATE TABLE customers (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT, status TEXT)');$entity=(new Entity())->hydrate(['name'=>'customer','store'=>'store://test','source'=>['table'=>'customers','primaryKey'=>'id'],'fields'=>['id'=>['dataType'=>'integer'],'email'=>['dataType'=>'string'],'status'=>['dataType'=>'string']]]);$repo=new SqlRepository($pdo,$entity);$id=$repo->create(['email'=>'b@example.com','status'=>'active']);$repo->create(['email'=>'a@example.com','status'=>'active']);self::assertSame('b@example.com',$repo->find($id)['email']);self::assertSame(['a@example.com','b@example.com'],array_column($repo->query(['status'=>'active'],['email'=>'asc']),'email'));}
}
