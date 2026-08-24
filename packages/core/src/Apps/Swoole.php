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
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\Http\Server;

final class Swoole implements App
{
    private Pipeline $pipeline;
    private Server $server;

    public Kernel $kernel {
        get => $this->kernelInstance;
    }

    public function __construct(
        private readonly Kernel $kernelInstance,
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 9501
    ) {
        $this->pipeline = new Pipeline();
        $this->server = new Server($this->host, $this->port);
    }

    public function use(Middleware|Closure ...$middleware): self
    {
        $this->pipeline->pipe(...$middleware);
        return $this;
    }

    public function run(mixed ...$args): void
    {
        $settings = $args[0] ?? [];
        if (!empty($settings) && is_array($settings)) {
            $this->server->set($settings);
        }

        $this->server->on('request', function (Request $request, Response $response): void {
            // 1. Translate OpenSwoole Request into a typed HttpIntent
            $intent = HttpTranslator::fromSwoole($request);
            $codex = $this->kernelInstance->codex;

            // 2. Dispatch through pipeline per coroutine request
            $this->pipeline->send($intent, function (Intent $i) use ($codex, $response) {
                if ($codex->has($i->action)) {
                    $handler = $codex->get($i->action);
                    $result = $handler($i);

                    $response->end(is_array($result) ? json_encode($result) : (string) $result);
                    return $result;
                }

                $response->status(404);
                $response->end('Not Found');
                return null;
            });
        });

        $this->server->start();
    }
}