<?php

declare(strict_types=1);

use Codejitsu\Enums\Scrolls\Types;
use Codejitsu\Scrolls\Types\Context;

it('creates a markdown-backed context scroll', function (): void {
    $scroll = new Context();
    $scroll->hydrate([
        'name' => 'architecture/codex',
        'tags' => ['architecture', 'agent'],
        'data' => '# Codex\n\nThe resource index.',
    ]);

    expect($scroll)
        ->toBeInstanceOf(Context::class)
        ->and($scroll->type)->toBe(Types::CONTEXT)
        ->and($scroll->name)->toBe('architecture/codex')
        ->and($scroll->tags)->toBe(['architecture', 'agent'])
        ->and($scroll->content())->toBe("# Codex\n\nThe resource index.");
});
