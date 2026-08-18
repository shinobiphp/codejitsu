<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Scrolls;

use Codejitsu\Enums\Scrolls\Types as ScrollTypes;

interface Store
{
    /**
     * Check if an envelope exists in the store for the given scroll type and name.
     */
    public function has(ScrollTypes $type, string$name): bool;

    /**
     * Retrieve an envelope from the store.
     */
    public function get(ScrollTypes $type, string$name): ?Envelope;

    /**
     * Retrieve all available envelopes for a specific scroll type.
     *
     * @return array<string, Envelope>
     */
    public function all(ScrollTypes $type): array;
}