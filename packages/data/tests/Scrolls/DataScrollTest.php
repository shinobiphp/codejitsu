<?php

declare(strict_types=1);

namespace Codejitsu\Data\Tests\Scrolls;

use Codejitsu\Data\Scrolls\Entity;
use Codejitsu\Data\Scrolls\Field;
use Codejitsu\Data\Scrolls\Repository;
use Codejitsu\Data\Scrolls\Store;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DataScrollTest extends TestCase
{
    public function testFieldCapturesTypeSchemaStrategiesAndQueryCapabilities(): void
    {
        $field = (new Field())->hydrate([
            'name' => 'customer/email', 'dataType' => 'string', 'schema' => 'schema://fields/email',
            'strategies' => ['normalize' => ['strategy://data/normalize-email']],
            'query' => ['filterable' => true, 'sortable' => true, 'operators' => ['eq', 'contains']],
        ]);
        self::assertSame('string', $field->dataType);
        self::assertSame(['strategy://data/normalize-email'], $field->strategies['normalize']);
    }

    public function testEntityOwnsStorageSourcesFieldsAndLifecycleHooks(): void
    {
        $entity = (new Entity())->hydrate([
            'name' => 'customer', 'store' => 'store://production-psql',
            'source' => ['table' => 'customers'],
            'fields' => ['email' => 'field://customer/email'],
            'sources' => ['legacy' => ['store' => 'store://legacy-mysql', 'resource' => ['table' => 'customers'], 'fields' => ['email' => 'email_address']]],
            'hooks' => ['afterCommit' => ['capability://customers/customer-created']],
        ]);
        self::assertSame('store://production-psql', $entity->store);
        self::assertSame('email_address', $entity->sources['legacy']['fields']['email']);
    }

    public function testStoreAcceptsSupportedRelationalDrivers(): void
    {
        foreach (['sqlite', 'mysql', 'pgsql'] as $driver) {
            self::assertSame($driver, (new Store())->hydrate(['name' => $driver, 'driver' => $driver, 'connection' => []])->driver);
        }
    }

    public function testStoreRejectsUnsupportedDriver(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Store())->hydrate(['name' => 'bad', 'driver' => 'oracle']);
    }

    public function testRepositoryDefinesNamedAndSafeDynamicQueries(): void
    {
        $repository = (new Repository())->hydrate([
            'name' => 'customer', 'entity' => 'entity://customer',
            'queries' => ['active' => ['filters' => ['status' => 'active'], 'order' => ['createdAt' => 'desc']]],
            'dynamic' => ['filters' => ['status'], 'ordering' => ['createdAt'], 'maxLimit' => 100],
        ]);
        self::assertSame(100, $repository->dynamic['maxLimit']);
    }
}
