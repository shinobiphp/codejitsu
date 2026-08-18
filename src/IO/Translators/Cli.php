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
        $filtered = array_values(array_filter($argv, static fn (string $a) => 
            !str_starts_with($a, '--env=') && !str_starts_with($a, '--root=')
        ));

        $command = $filtered[1] ?? 'help';
        $rawArgs = array_slice($filtered, 2);

        $payload = [];
        $flags = [];

        foreach ($rawArgs as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $flags[$parts[0]] = $parts[1] ?? true;
            } else {
                $payload[] = $arg;
            }
        }

        // Construct your strict Identity structure for the CLI intent
        $identity = new Identity(
            type: IdentityType::Intent,
            identifier: new Identifier("cli.{$command}"),
            version: new Version() // or parse from flags/environment if needed
        );

        $metadata = new Metadata($identity, new Collection([
            'flags' => $flags,
            'argc' => count($filtered),
            'raw' => $argv,
        ]));

        return new CliIntent(
            action: "cli.{$command}",
            payload: $payload,
            metadata: $metadata
        );
    }
}