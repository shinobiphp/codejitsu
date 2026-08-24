<?php
declare(strict_types=1);

namespace Codejitsu\Contracts\Uri;

interface ResolverDriver
{
    /**
     * Resolves the URI into a fully contextualized Resolved payload.
     */
    public function resolve(Uri $uri): Resolved;
}