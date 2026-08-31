<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls\Types;

use Codejitsu\Enums\Codecs;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Scrolls\Scroll;
use InvalidArgumentException;

final class Package extends Scroll
{
    public const ScrollTypes TYPE = ScrollTypes::PACKAGE;

    public function hydrate(array $data): static
    {
        $name = strtolower(trim((string) ($data['name'] ?? '')));
        if (preg_match('/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/', $name) !== 1) {
            throw new InvalidArgumentException('Invalid package field [name].');
        }
        $data['name'] = $name;
        foreach (['keywords', 'tags'] as $field) {
            if (isset($data[$field])) {
                if (!is_array($data[$field])) {
                    throw new InvalidArgumentException(sprintf('Invalid package field [%s].', $field));
                }
                $data[$field] = array_values(array_unique(array_map(
                    static fn (mixed $value): string => strtolower(trim((string) $value)),
                    $data[$field],
                )));
            }
        }
        foreach (['homepage', 'documentation'] as $field) {
            if (isset($data[$field]) && filter_var($data[$field], FILTER_VALIDATE_URL) === false) {
                throw new InvalidArgumentException(sprintf('Invalid package field [%s].', $field));
            }
        }
        foreach (($data['configuration'] ?? []) as $index => $uri) {
            if (!is_string($uri) || preg_match('/^[a-z][a-z0-9+.-]*:\/\/.+$/i', $uri) !== 1) {
                throw new InvalidArgumentException(sprintf('Invalid package field [configuration.%s].', $index));
            }
        }
        foreach (($data['types'] ?? []) as $type => $definition) {
            $this->validateType((string) $type, $definition);
        }
        foreach (($data['sources'] ?? []) as $alias => $source) {
            $path = is_array($source) ? ($source['path'] ?? null) : null;
            if (preg_match('/^[a-z][a-z0-9_-]*$/', (string) $alias) !== 1
                || !is_string($path) || !$this->isRelativePath($path)) {
                throw new InvalidArgumentException(sprintf('Invalid package field [sources.%s.path].', $alias));
            }
            $data['sources'][$alias]['path'] = trim(str_replace('\\', '/', $path), '/');
        }
        foreach (['compatibility', 'capabilities', 'dependencies'] as $field) {
            if (isset($data[$field]) && !is_array($data[$field])) {
                throw new InvalidArgumentException(sprintf('Invalid package field [%s].', $field));
            }
        }
        return parent::hydrate($data);
    }

    /** @return array<string,array<string,mixed>> */
    public function typeDeclarations(): array
    {
        return $this->attributes['types'] ?? [];
    }

    /** @return array<string,array{path:string}> */
    public function sourceDeclarations(): array
    {
        return $this->attributes['sources'] ?? [];
    }

    private function validateType(string $type, mixed $definition): void
    {
        if (!is_array($definition)) {
            throw new InvalidArgumentException(sprintf('Invalid package field [types.%s].', $type));
        }
        foreach (['plural', 'extension', 'scheme', 'class'] as $field) {
            if (!isset($definition[$field]) || !is_string($definition[$field])) {
                throw new InvalidArgumentException(sprintf('Invalid package field [types.%s.%s].', $type, $field));
            }
        }
        if (preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\\\\)*[A-Za-z_][A-Za-z0-9_]*$/', $definition['class']) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid package field [types.%s.class].', $type));
        }
        if (Codecs::tryFrom(strtolower((string) ($definition['codec'] ?? 'neon'))) === null) {
            throw new InvalidArgumentException(sprintf('Invalid package field [types.%s.codec].', $type));
        }
    }

    private function isRelativePath(string $path): bool
    {
        $path = str_replace('\\', '/', trim($path));
        return $path !== '' && !str_starts_with($path, '/')
            && preg_match('/^[A-Za-z]:\//', $path) !== 1
            && !in_array('..', explode('/', $path), true);
    }
}
