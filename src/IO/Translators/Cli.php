<?php

declare(strict_types=1);

namespace Codejitsu\IO\Translators;

use Codejitsu\Enums\Identity\Types as IdentityType;
use Codejitsu\Identity\Identity;
use Codejitsu\Identity\Identifier;
use Codejitsu\IO\CliIntent;
use Codejitsu\Metadata;
use Codejitsu\Collection;
use Codejitsu\ValueObjects\Version;

final class Cli
{
    public static function translate(array $argv): CliIntent
    {
        $filtered = array_values(array_filter(
            $argv,
            static fn (string $arg): bool =>
                !str_starts_with($arg, '--env=') && !str_starts_with($arg, '--root='),
        ));

        $command = strtolower((string) ($filtered[1] ?? ''));
        $rawArgs = array_slice($filtered, 2);

        if (in_array($command, ['help', '--help', '-h'], true)) {
            $command = '';
        }

        $payload = [];
        $flags = [];

        foreach ($rawArgs as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $flags[$parts[0]] = $parts[1] ?? true;
                continue;
            }

            $payload[] = $arg;
        }

        $identity = new Identity(
            type: IdentityType::Intent,
            identifier: new Identifier('cli.' . ($command === '' ? 'help' : $command)),
            version: new Version(),
        );

        return new CliIntent(
            action: $command,
            payload: $payload,
            metadata: new Metadata($identity, new Collection([
                'flags' => $flags,
                'argc' => count($filtered),
                'raw' => $argv,
            ])),
        );
    }
}
