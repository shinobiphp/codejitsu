<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Closure;
use Codejitsu\Contracts\Scrolls\Scroll;
use InvalidArgumentException;
use UnexpectedValueException;

final readonly class DiscoveredResource
{
    /**
     * @param array<string> $tags
     * @param array<string, mixed> $attributes
     * @param array<string> $references
     * @param Closure(): Scroll $loader
     */
    public function __construct(
        public TypeDefinition $type,
        public string $name,
        public string $version,
        public array $tags,
        public array $attributes,
        public array $references,
        public string $locator,
        private Closure $loader,
    ) {
        if (!preg_match('/^[a-z0-9][a-z0-9_.\/-]*$/i', trim($name, '/'))) {
            throw new InvalidArgumentException(sprintf('Invalid Scroll logical path [%s].', $name));
        }
        if (!preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version)) {
            throw new InvalidArgumentException(sprintf('Invalid Scroll version [%s].', $version));
        }
    }

    public function hydrate(): Scroll
    {
        $scroll = ($this->loader)();
        if (!$scroll instanceof Scroll) {
            throw new UnexpectedValueException(sprintf('Loader for [%s] returned an invalid Scroll.', $this->name));
        }
        return $scroll;
    }
}
