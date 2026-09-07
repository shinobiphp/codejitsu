<?php
declare(strict_types=1);
namespace Codejitsu\Data\Tests\Scrolls;
use Codejitsu\Data\Scrolls\Entity;use Codejitsu\Data\Scrolls\Field;use Codejitsu\Data\Scrolls\Repository;use Codejitsu\Data\Scrolls\Store;use Codejitsu\Packages\InstalledPackage;use Codejitsu\Packages\PackageCompiler;use PHPUnit\Framework\TestCase;
final class TypeRegistrationTest extends TestCase
{
 public function testPackageDeclaresDataTypesAndResources():void{$root=dirname(__DIR__,2);$compiled=(new PackageCompiler())->compile([new InstalledPackage('codejitsu/data','0.1.0',$root,$root.'/codejitsu.package')]);$types=$compiled['packages'][0]['types'];self::assertSame(Entity::class,$types['entity']['class']);self::assertSame(Field::class,$types['field']['class']);self::assertSame(Repository::class,$types['repository']['class']);self::assertSame(Store::class,$types['store']['class']);self::assertFileExists($root.'/resources/commands/data.cmd');self::assertFileExists($root.'/resources/capabilities/data-build.capability');self::assertFileExists($root.'/resources/contexts/architecture.ctx');}
}
