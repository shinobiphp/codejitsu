<?php

declare(strict_types=1);

namespace Codejitsu\Substrate;

use Codejitsu\ExecutionContext;
use Codejitsu\Substrate;
use LogicException;

final class Php implements Substrate
{
    public static function detect(string $source): string
    {
        return (new Detector())->detect($source);
    }

    public function execute(string $source, ExecutionContext $context): mixed
    {
        $source = preg_replace('/^\s*#![^\r\n]*\r?\n/', '', $source, 1);
        if ($source === null) {
            throw new LogicException('Unable to prepare PHP source.');
        }

        $source = preg_replace('/^\s*<\?php\s*/', '', $source, 1, $phpTagCount);
        if ($source === null) {
            throw new LogicException('Unable to prepare PHP source.');
        }

        if ($phpTagCount === 0) {
            throw new LogicException('PHP source must begin with <?php.');
        }

        $runner = static function (ExecutionContext $context) use ($source): mixed {
            return eval($source);
        };

        return $runner($context);
    }
}
