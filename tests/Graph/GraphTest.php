<?php

declare(strict_types=1);

use Codejitsu\Graph\Edge;
use Codejitsu\Graph\Graph;
use Codejitsu\Graph\Node;
use LogicException;

it('stores nodes and named relationships', function (): void {
    $graph = new Graph();
    $graph->add(new Node('architecture'));
    $graph->add(new Node('scrolls'));
    $graph->connect(new Edge('architecture', 'scrolls', 'scrolls', 'contains'));

    expect($graph->node('scrolls'))->toBeInstanceOf(Node::class)
        ->and($graph->edge('architecture', 'scrolls'))->toBeInstanceOf(Edge::class)
        ->and($graph->outgoing('architecture'))->toHaveCount(1);
});

it('rejects duplicate nodes', function (): void {
    $graph = new Graph();
    $graph->add(new Node('architecture'));

    expect(fn (): mixed => $graph->add(new Node('architecture')))
        ->toThrow(LogicException::class);
});

it('requires both edge endpoints to exist', function (): void {
    $graph = new Graph();
    $graph->add(new Node('architecture'));

    expect(fn (): mixed => $graph->connect(new Edge('architecture', 'missing', 'missing')))
        ->toThrow(LogicException::class);
});
