<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Substrate;

use Codejitsu\ExecutionContext;
use Codejitsu\Substrate;
use Codejitsu\Substrate\Detector;
use Codejitsu\Substrate\Resolver;
use Codejitsu\SubstrateRegistry;
use PHPUnit\Framework\TestCase;

final class SubstrateRegistryTest extends TestCase
{
    public function testRegistryStoresAndReturnsSubstrates(): void
    {
        $registry = new SubstrateRegistry();
        $substrate = new FakeSubstrate();

        $registry->register('fake', $substrate);

        self::assertTrue($registry->has('fake'));
        self::assertSame($substrate, $registry->get('fake'));
        self::assertSame(['fake'], $registry->names());
    }

    public function testExplicitSubstrateWinsOverDetection(): void
    {
        $registry = new SubstrateRegistry();
        $php = new FakeSubstrate('php');
        $lua = new FakeSubstrate('lua');
        $registry->register('php', $php);
        $registry->register('lua', $lua);

        $resolver = new Resolver($registry, new Detector());

        self::assertSame($lua, $resolver->resolve('lua', '<?php return 1;'));
    }

    public function testAutoDetectionFallsBackToPhp(): void
    {
        $registry = new SubstrateRegistry();
        $php = new FakeSubstrate('php');
        $lua = new FakeSubstrate('lua');
        $registry->register('php', $php);
        $registry->register('lua', $lua);

        $resolver = new Resolver($registry, new Detector());

        self::assertSame($php, $resolver->resolve('auto', 'return 1;'));
    }
}

final class FakeSubstrate implements Substrate
{
    public function __construct(private readonly string $name = 'fake')
    {
    }

    public function execute(string $source, ExecutionContext $context): mixed
    {
        return $this->name;
    }
}
