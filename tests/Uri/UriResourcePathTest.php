<?php

declare(strict_types=1);

use Codejitsu\Uri\Uri;

it('combines URI authority and path into one logical resource path', function (): void {
    $uri = Uri::make('context://architecture/scrolls@tenant.global#1.0.0');

    expect($uri->target)->toBe('architecture')
        ->and($uri->path)->toBe('scrolls')
        ->and($uri->resourcePath)->toBe('architecture/scrolls')
        ->and($uri->sources)->toBe(['tenant', 'global'])
        ->and((string) $uri)->toBe('context://architecture/scrolls@tenant.global#1.0.0');
});

it('uses the target as the logical path for root resources', function (): void {
    $uri = Uri::make('app://archiq');

    expect($uri->resourcePath)->toBe('archiq');
});
