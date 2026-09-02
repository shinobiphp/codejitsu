<?php
declare(strict_types=1);
namespace Codejitsu\Tests\Console;

use Codejitsu\Console\CommandRegistry;
use Codejitsu\Scrolls\Types\Command;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CommandRegistryTest extends TestCase
{
    public function testItMergesNonConflictingNamespaceContributions(): void
    {
        $core=(new Command())->hydrate(['name'=>'make','commands'=>['context'=>['target'=>'Core::context']]]);
        $ai=(new Command())->hydrate(['name'=>'make','commands'=>['spark'=>['target'=>'Ai::spark']]]);
        $commands=(new CommandRegistry())->merge([$core,$ai]);
        self::assertSame(['context','spark'],array_keys($commands[0]->commands()));
    }

    public function testItRejectsConflictingChildren(): void
    {
        $a=(new Command())->hydrate(['name'=>'make','commands'=>['spark'=>['target'=>'A::spark']]]);
        $b=(new Command())->hydrate(['name'=>'make','commands'=>['spark'=>['target'=>'B::spark']]]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('make:spark');
        (new CommandRegistry())->merge([$a,$b]);
    }

    public function testMergingDoesNotMutateSourceCommands(): void
    {
        $core=(new Command())->hydrate(['name'=>'make','commands'=>['context'=>['target'=>'Core::context']]]);
        $ai=(new Command())->hydrate(['name'=>'make','commands'=>['spark'=>['target'=>'Ai::spark']]]);
        $registry=new CommandRegistry();

        self::assertSame(['context','spark'],array_keys($registry->merge([$core,$ai])[0]->commands()));
        self::assertSame(['context','spark'],array_keys($registry->merge([$core,$ai])[0]->commands()));
        self::assertSame(['context'],array_keys($core->commands()));
        self::assertSame(['spark'],array_keys($ai->commands()));
    }

    public function testItSortsNamespacesAndChildrenAlphabetically(): void
    {
        $make=(new Command())->hydrate(['name'=>'make','commands'=>[
            'vessel'=>['target'=>'Ai::vessel'],
            'context'=>['target'=>'Core::context'],
            'spark'=>['target'=>'Ai::spark'],
        ]]);
        $ai=(new Command())->hydrate(['name'=>'ai','commands'=>[
            'tui'=>['target'=>'Ai::tui'],
            'run'=>['target'=>'Ai::run'],
        ]]);

        $commands=(new CommandRegistry())->merge([$make,$ai]);

        self::assertSame(['ai','make'],array_map(static fn(Command $command): string=>$command->name,$commands));
        self::assertSame(['run','tui'],array_keys($commands[0]->commands()));
        self::assertSame(['context','spark','vessel'],array_keys($commands[1]->commands()));
    }
}
