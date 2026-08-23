<?php

declare(strict_types=1);

namespace Codejitsu\Graph;

use LogicException;

final class Graph
{
    /** @var array<string, Node> */
    private array $nodes = [];

    /** @var list<Edge> */
    private array $edges = [];

    public function add(Node $node): void
    {
        if (isset($this->nodes[$node->id])) {
            throw new LogicException(sprintf('Graph node [%s] already exists.', $node->id));
        }

        $this->nodes[$node->id] = $node;
    }

    public function connect(Edge $edge): void
    {
        if (!isset($this->nodes[$edge->from], $this->nodes[$edge->to])) {
            throw new LogicException('Graph edges must connect existing nodes.');
        }

        $this->edges[] = $edge;
    }

    public function node(string $id): ?Node
    {
        return $this->nodes[$id] ?? null;
    }

    /** @return list<Node> */
    public function nodes(): array
    {
        return array_values($this->nodes);
    }

    /** @return list<Edge> */
    public function edges(): array
    {
        return $this->edges;
    }

    /** @return list<Edge> */
    public function outgoing(string $id): array
    {
        return array_values(array_filter(
            $this->edges,
            static fn (Edge $edge): bool => $edge->from === $id,
        ));
    }

    public function edge(string $from, string $name): ?Edge
    {
        foreach ($this->edges as $edge) {
            if ($edge->from === $from && $edge->name === $name) {
                return $edge;
            }
        }

        return null;
    }
}
