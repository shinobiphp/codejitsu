<?php

declare(strict_types=1);

namespace Codejitsu\Commands;

use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\ScrollCodex;

final class Scrolls
{
    public static function list(ExecutionContext $context): string
    {
        $codex = $context->codex ?? throw new \LogicException('Scroll list capability requires a ScrollCodex.');

        $entries = $codex->query();
        if ($entries === []) {
            return "No Scrolls are currently registered.\n";
        }

        $output = '';
        foreach ($entries as $entry) {
            $output .= sprintf(
                "%-12s %-32s %s\n",
                $entry->type,
                $entry->uri,
                $entry->source,
            );
        }

        return $output;
    }
}
