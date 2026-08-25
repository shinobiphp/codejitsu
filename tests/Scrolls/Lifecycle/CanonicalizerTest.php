<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Scrolls\Lifecycle;

use Codejitsu\Scrolls\Lifecycle\Canonicalizer;
use Codejitsu\Scrolls\Types\Command;
use PHPUnit\Framework\TestCase;

final class CanonicalizerTest extends TestCase
{
    public function testItSortsAssociativeKeysRecursively(): void
    {
        $canonicalizer = new Canonicalizer();

        self::assertSame(
            $canonicalizer->array(['b' => 2, 'a' => ['d' => 4, 'c' => 3]]),
            $canonicalizer->array(['a' => ['c' => 3, 'd' => 4], 'b' => 2]),
        );
    }

    public function testItPreservesListOrdering(): void
    {
        $canonicalizer = new Canonicalizer();

        self::assertSame(
            [1, 2, 3],
            $canonicalizer->array([1, 2, 3]),
        );
    }

    public function testLifecycleMetadataDoesNotAffectCanonicalPayload(): void
    {
        $canonicalizer = new Canonicalizer();
        $plain = new Command();
        $signed = new Command();

        $plain->hydrate([
            'name' => 'hello',
            'description' => 'Hello',
            'signature' => ['algorithm' => 'ed25519', 'value' => 'one'],
            'seal' => ['algorithm' => 'xchacha20-poly1305', 'value' => 'one'],
        ]);
        $signed->hydrate([
            'name' => 'hello',
            'description' => 'Hello',
            'signature' => ['algorithm' => 'ed25519', 'value' => 'two'],
            'seal' => ['algorithm' => 'xchacha20-poly1305', 'value' => 'two'],
        ]);

        self::assertSame(
            $canonicalizer->scroll($plain),
            $canonicalizer->scroll($signed),
        );
    }
}
