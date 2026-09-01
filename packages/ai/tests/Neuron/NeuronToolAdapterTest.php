<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Tests\Neuron;

use Codejitsu\Ai\Definitions\ToolDefinition;
use Codejitsu\Ai\Neuron\NeuronToolAdapter;
use Codejitsu\Ai\Tools\AllowNamedTools;
use Codejitsu\Ai\Tools\ToolRegistry;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Capability;
use PHPUnit\Framework\TestCase;

final class NeuronToolAdapterTest extends TestCase
{
    public function testItRoutesNeuronInputsThroughCodejitsuPolicy(): void
    {
        $codex = new ScrollCodex();
        $codex->registerScroll((new Capability())->hydrate(['name' => 'echo', 'version' => '1.0.0', 'target' => fn ($context) => $context->arguments['value']]));
        $definition = ToolDefinition::fromArray([
            'name' => 'echo', 'description' => 'Echo.', 'capability' => 'capability://echo',
            'inputSchema' => ['type' => 'object', 'required' => ['value'], 'properties' => ['value' => ['type' => 'string']]],
        ]);
        $tool = new NeuronToolAdapter($definition, new ToolRegistry($codex), new AllowNamedTools(['echo']));
        $tool->setInputs(['value' => 'hello'])->execute();
        self::assertSame('hello', $tool->getResult());
        self::assertSame(1, $tool->getMaxRuns());
    }
}
