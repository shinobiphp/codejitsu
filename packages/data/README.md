# Codejitsu Data

Codejitsu Data defines a declarative data graph that Buildshido can compile into optimized PHP artifacts.

## Ownership

- `field://` describes a reusable value, schema constraints, query capabilities, and ordered Strategy references.
- `entity://` composes Fields and owns stores, physical resources, legacy source mappings, projections, and lifecycle hooks.
- `repository://` defines named queries and the safe dynamic filter/order surface.
- `store://` defines a driver, connection references, and operational options.
- `schema://` remains owned by Codejitsu Schema.
- `strategy://` is a cross-package design pattern; Data consumes it but does not own it.

The initial relational adapters support SQLite, MySQL, and PostgreSQL through PDO. MongoDB is intentionally deferred to a native document adapter.

## First build target

The first compiler target resolves and validates an Entity graph, then emits immutable PHP DTO source and a deterministic manifest. Generated artifacts belong under `var/build/data`; generated files are never edited by hand.
