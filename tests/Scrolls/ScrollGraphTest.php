<?php

declare(strict_types=1);

use Codejitsu\Scrolls\Types\Context;
use Codejitsu\Graph\Edge;

it('normalizes named scroll references into graph edges', function (): void {
    $scroll = (new Context())->hydrate([
        'name' => 'architecture/codex',
        'references' => [
            '$schema' => 'schema://scroll#1.0.0',
        ],
    ]);

    $edges = $scroll->references();

    expect($edges)->toHaveCount(1)
        ->and($edges[0])->toBeInstanceOf(Edge::class)
        ->and($edges[0]->name)->toBe('schema')
        ->and($edges[0]->type)->toBe('reference')
        ->and($edges[0]->to)->toBe('uri:schema://scroll#1.0.0');
});

it('supports nested logical scroll paths', function (): void {
    $scroll = (new Context())->hydrate([
        'name' => 'architecture/scrolls',
    ]);

    expect($scroll->name)->toBe('architecture/scrolls')
        ->and($scroll->graph()->node('context://architecture/scrolls#1.0.0'))->not->toBeNull();
});
