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

    /** @return list<Edge> */
    public function incoming(string $id): array
    {
        return array_values(array_filter(
            $this->edges,
            static fn (Edge $edge): bool => $edge->to === $id,
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

    public function related(string $id, string $name): ?Node
    {
        $edge = $this->edge($id, $name);

        return $edge === null ? null : $this->node($edge->to);
    }

    /** @return list<Node> */
    public function neighbors(string $id): array
    {
        $ids = [];

        foreach ($this->outgoing($id) as $edge) {
            $ids[$edge->to] = true;
        }

        foreach ($this->incoming($id) as $edge) {
            $ids[$edge->from] = true;
        }

        return array_values(array_filter(
            array_map(fn (string $nodeId): ?Node => $this->node($nodeId), array_keys($ids)),
        ));
    }
}
