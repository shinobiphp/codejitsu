<?php

declare(strict_types=1);

namespace Codejitsu\Substrate;

use Codejitsu\ExecutionContext;
use Codejitsu\Substrate;
use LogicException;

final class Javascript implements Substrate
{
    public function execute(string $source, ExecutionContext $context): mixed
    {
        if (!class_exists('V8Js')) {
            throw new LogicException('V8Js extension is not installed.');
        }

        $v8 = new \V8Js('Codejitsu', [
            'arguments' => $context->arguments,
        ], [], true);

        return $v8->executeString(
            $source,
            'codejitsu-scroll.js',
            \V8Js::FLAG_NONE,
            $context->policy->timeoutMilliseconds,
            $context->policy->memoryBytes,
        );
    }
}
