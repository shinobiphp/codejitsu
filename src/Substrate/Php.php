<?php

declare(strict_types=1);

namespace Codejitsu\Substrate;

use Codejitsu\ExecutionContext;
use Codejitsu\Substrate;

final class Php implements Substrate
{
    public static function detect(string $source): string
    {
        return (new Detector())->detect($source);
    }

    public function execute(string $source, ExecutionContext $context): mixed
    {
        return (new PhpRunner())->execute($source, $context);
    }
}
