<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tests\Tools;

use Codejitsu\Ai\Definitions\ToolDefinition;
use Codejitsu\Ai\Exceptions\DefinitionException;
use Codejitsu\Ai\Tools\AllowNamedTools;
use Codejitsu\Ai\Tools\DenyConsequentialTools;
use Codejitsu\Ai\Tools\ToolRegistry;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Capability;
use PHPUnit\Framework\TestCase;

final class ToolRegistryTest extends TestCase
{
    public function testConsequentialToolIsDeniedBeforeCapabilityRuns(): void
    {
        $called = false;
        $registry = $this->registry(function () use (&$called): string { $called = true; return 'called'; });

        $this->expectExceptionMessage('Tool [write] was not approved.');
        try { $registry->execute($this->tool(), ['value' => 'x'], new DenyConsequentialTools()); }
        finally { self::assertFalse($called); }
    }

    public function testApprovedToolValidatesAndDelegatesToCapability(): void
    {
        $registry = $this->registry(fn ($context): array => ['seen' => $context->arguments['value']]);
        $result = $registry->execute($this->tool(), ['value' => 'hello'], new AllowNamedTools(['write']));
        self::assertSame(['seen' => 'hello'], $result->value);
    }

    public function testInvalidArgumentsFailBeforeExecution(): void
    {
        $called = false;
        $registry = $this->registry(function () use (&$called): void { $called = true; });
        try { $registry->execute($this->tool(), [], new AllowNamedTools(['write'])); self::fail('Invalid arguments accepted.'); }
        catch (\Throwable) { self::assertFalse($called); }
    }

    public function testMaxRunsIsEnforcedPerRegistryRequestScope(): void
    {
        $registry = $this->registry(fn (): string => 'ok');
        $tool = $this->tool();
        $approval = new AllowNamedTools(['write']);
        $registry->execute($tool, ['value' => 'one'], $approval);
        $this->expectException(DefinitionException::class);
        $this->expectExceptionMessage('maximum run count');
        $registry->execute($tool, ['value' => 'two'], $approval);
    }

    public function testInternalPolicyMetadataIsInjectedAfterSchemaValidation(): void
    {
        $codex = new ScrollCodex();
        $codex->registerScroll((new Capability())->hydrate([
            'name' => 'write', 'version' => '1.0.0',
            'target' => fn ($context): array => $context->arguments['_codejitsu'],
        ]));
        $registry = new ToolRegistry($codex, executionMetadata: ['allowedContexts' => ['context://state']]);
        $result = $registry->execute($this->tool(), ['value' => 'hello'], new AllowNamedTools(['write']));
        self::assertSame(['allowedContexts' => ['context://state']], $result->value);
    }

    private function registry(callable $target): ToolRegistry
    {
        $codex = new ScrollCodex();
        $codex->registerScroll((new Capability())->hydrate(['name' => 'write', 'version' => '1.0.0', 'target' => $target]));
        return new ToolRegistry($codex);
    }

    private function tool(): ToolDefinition
    {
        return ToolDefinition::fromArray([
            'name' => 'write', 'description' => 'Write a value.', 'capability' => 'capability://write',
            'inputSchema' => ['type' => 'object', 'required' => ['value'], 'additionalProperties' => false, 'properties' => ['value' => ['type' => 'string']]],
            'consequential' => true, 'maxRuns' => 1,
        ]);
    }
}
