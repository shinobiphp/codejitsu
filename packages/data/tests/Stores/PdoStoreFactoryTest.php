<?php
declare(strict_types=1);
namespace Codejitsu\Data\Tests\Stores;
use Codejitsu\Data\Scrolls\Store;use Codejitsu\Data\Stores\PdoStoreFactory;use PHPUnit\Framework\TestCase;
final class PdoStoreFactoryTest extends TestCase
{
 public function testBuildsDriverSpecificDsns():void{$f=new PdoStoreFactory();self::assertSame('sqlite::memory:',$f->dsn((new Store())->hydrate(['name'=>'test','driver'=>'sqlite','connection'=>['database'=>':memory:']])));self::assertSame('mysql:host=db;port=3307;dbname=app;charset=utf8mb4',$f->dsn((new Store())->hydrate(['name'=>'mysql','driver'=>'mysql','connection'=>['host'=>'db','port'=>3307,'database'=>'app']])));self::assertSame('pgsql:host=db;port=5433;dbname=app',$f->dsn((new Store())->hydrate(['name'=>'pg','driver'=>'pgsql','connection'=>['host'=>'db','port'=>5433,'database'=>'app']])));}
 public function testConnectsToSqlite():void{$pdo=(new PdoStoreFactory())->connect((new Store())->hydrate(['name'=>'test','driver'=>'sqlite','connection'=>['database'=>':memory:']]));self::assertSame('sqlite',$pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));}
}
