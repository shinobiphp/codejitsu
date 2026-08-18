<?php

declare(strict_types=1);

namespace Codejitsu\Apps;

use Codejitsu\Contracts\App;
use Codejitsu\Contracts\Intent;
use Codejitsu\Contracts\Middleware;
use Codejitsu\IO\Translators\Cli as CliTranslator;
use Codejitsu\Kernel\Kernel;
use Codejitsu\Pipeline\Pipeline;
use Closure;

final class Cli implements App
{
    private Pipeline $pipeline;

    public Kernel $kernel {
        get => $this->kernelInstance;
    }

    public function __construct(
        private readonly Kernel $kernelInstance
    ) {
        $this->pipeline = new Pipeline();
    }

    public function use(Middleware|Closure ...$middleware): self
    {
        $this->pipeline->pipe(...$middleware);
        return $this;
    }

    public function run(mixed ...$args): int
    {
        $rawArgv = $args[0] ?? $_SERVER['argv'] ?? [];
        
        // 1. Translate raw CLI inputs into a typed CliIntent
        $intent = CliTranslator::translate($rawArgv);
        var_dump($intent);
        exit;
        $codex = $this->kernelInstance->codex;

        // 2. Dispatch through the pipeline and execute Codex command
        $result = $this->pipeline->send($intent, function (Intent $i) use ($codex) {
            if (!$codex->has($i->action)) {
                fwrite(STDERR, "Unknown command action: {$i->action}\n");
                return 1;
            }

            $handler = $codex->get($i->action);
            return (int) $handler($i);
        });

        return is_int($result) ? $result : 0;
    }
}