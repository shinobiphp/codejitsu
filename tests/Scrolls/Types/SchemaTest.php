<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls\Types;

use Codejitsu\Scrolls\Types\Schema;
use PHPUnit\Framework\TestCase;

final class SchemaTest extends TestCase
{
    public function testItReturnsValidDataWhenTheDefinitionMatches(): void
    {
        $schema = (new Schema())->hydrate([
            'name' => 'user',
            'type' => 'object',
            'required' => ['name'],
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ]);

        $data = ['name' => 'B'];

        self::assertSame($data, $schema($data));
        self::assertSame($data, $schema->execute($data));
    }

    public function testItRejectsInvalidData(): void
    {
        $schema = (new Schema())->hydrate([
            'name' => 'user',
            'type' => 'object',
            'required' => ['name'],
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ]);

        $this->expectException(\Throwable::class);

        $schema(['name' => 123]);
    }
}
