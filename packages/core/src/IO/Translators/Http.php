<?php

declare(strict_types=1);

namespace Codejitsu\IO\Translators;

use Codejitsu\Enums\Identity\Types as IdentityType;
use Codejitsu\Identity\Identity;
use Codejitsu\Identity\Identifier;
use Codejitsu\IO\HttpIntent;
use Codejitsu\Metadata;
use Codejitsu\Collection;
use Codejitsu\ValueObjects\Version;
use OpenSwoole\Http\Request as SwooleRequest;

final class Http
{
    public static function fromGlobals(): HttpIntent
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        $action = "web.{$method}.{$path}";

        $payload = array_merge($_GET, $_POST);
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $payload = array_merge($payload, $json);
            }
        }

        $identity = new Identity(
            type: IdentityType::Request,
            identifier: new Identifier("web.{$method}.{$path}"),
            version: new Version()
        );

        $metadata = new Metadata($identity, new Collection([
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'sapi' => PHP_SAPI,
        ]));

        return new HttpIntent(
            method: $method,
            path: $path,
            action: $action,
            payload: $payload,
            metadata: $metadata,
            headers: function_exists('getallheaders') ? (getallheaders() ?: []) : []
        );
    }

    public static function fromSwoole(SwooleRequest $request): HttpIntent
    {
        $method = strtoupper($request->server['request_method'] ?? 'GET');
        $path = '/' . trim($request->server['request_uri'] ?? '/', '/');
        $action = "swoole.{$method}.{$path}";

        $payload = array_merge($request->get ?? [], $request->post ?? []);
        $contentType = $request->header['content-type'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw = $request->getContent();
            $json = json_decode($raw ?: '', true);
            if (is_array($json)) {
                $payload = array_merge($payload, $json);
            }
        }

        $identity = new Identity(
            type: IdentityType::Request,
            identifier: new Identifier("swoole.{$method}.{$path}"),
            version: new Version()
        );

        $metadata = new Metadata($identity, new Collection([
            'ip' => $request->server['remote_addr'] ?? '127.0.0.1',
            'fd' => $request->fd,
        ]));

        return new HttpIntent(
            method: $method,
            path: $path,
            action: $action,
            payload: $payload,
            metadata: $metadata,
            headers: $request->header ?? []
        );
    }
}