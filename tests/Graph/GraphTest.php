<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Graph;

use Codejitsu\Graph\Edge;
use Codejitsu\Graph\Graph;
use Codejitsu\Graph\Node;
use LogicException;
use PHPUnit\Framework\TestCase;

final class GraphTest extends TestCase
{
    public function testStoresNodesAndNamedRelationships(): void
    {
        $graph = new Graph();
        $graph->add(new Node('architecture'));
        $graph->add(new Node('scrolls'));
        $graph->connect(new Edge('architecture', 'scrolls', 'scrolls', 'contains'));

        self::assertInstanceOf(Node::class, $graph->node('scrolls'));
        self::assertInstanceOf(Edge::class, $graph->edge('architecture', 'scrolls'));
        self::assertCount(1, $graph->outgoing('architecture'));
    }

    public function testRejectsDuplicateNodes(): void
    {
        $graph = new Graph();
        $graph->add(new Node('architecture'));

        $this->expectException(LogicException::class);
        $graph->add(new Node('architecture'));
    }

    public function testRequiresBothEdgeEndpointsToExist(): void
    {
        $graph = new Graph();
        $graph->add(new Node('architecture'));

        $this->expectException(LogicException::class);
        $graph->connect(new Edge('architecture', 'missing', 'missing'));
    }
}
