<?php

declare(strict_types=1);

namespace Codejitsu\Substrate;

use Codejitsu\ExecutionContext;
use Codejitsu\Substrate;
use LogicException;

final class Lua implements Substrate
{
    public function execute(string $source, ExecutionContext $context): mixed
    {
        if (!class_exists('LuaSandbox')) {
            throw new LogicException('LuaSandbox extension is not installed.');
        }

        $sandbox = new \LuaSandbox();
        $sandbox->setMemoryLimit($context->policy->memoryBytes);
        $sandbox->setCPULimit($context->policy->timeoutMilliseconds / 1000);

        $sandbox->registerLibrary('codejitsu', [
            'arguments' => static fn (): array => [$context->arguments],
        ]);

        $results = $sandbox->loadString($source, 'codejitsu-scroll')->call();

        return $results[0] ?? null;
    }
}
