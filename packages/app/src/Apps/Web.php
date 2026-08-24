<?php

declare(strict_types=1);

namespace Codejitsu\Apps;

use Codejitsu\Contracts\App;
use Codejitsu\Contracts\Intent;
use Codejitsu\Contracts\Middleware;
use Codejitsu\IO\Translators\Http as HttpTranslator;
use Codejitsu\Kernel\Kernel;
use Codejitsu\Pipeline\Pipeline;
use Closure;

final class Web implements App
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

    public function run(mixed ...$args): mixed
    {
        // 1. Translate HTTP superglobals into a typed HttpIntent
        $intent = HttpTranslator::fromGlobals();
        $codex = $this->kernelInstance->codex;

        // 2. Dispatch through pipeline and execute Codex route handler
        return $this->pipeline->send($intent, function (Intent $i) use ($codex) {
            if ($codex->has($i->action)) {
                $handler = $codex->get($i->action);
                $response = $handler($i);

                if (is_array($response)) {
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    return $response;
                }

                echo (string) $response;
                return $response;
            }

            http_response_code(404);
            echo '404 Not Found';
            return null;
        });
    }
}