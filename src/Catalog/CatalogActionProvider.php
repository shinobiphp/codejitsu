<?php
declare(strict_types=1);
namespace Codejitsu\Catalog;

interface CatalogActionProvider
{
    /** @param array<string,mixed> $entry */
    public function supports(array $entry): bool;

    /** @param array<string,mixed> $entry @return array<string,array{label:string,consequential:bool}> */
    public function actions(array $entry, string $root): array;

    /** @param array<string,mixed> $entry */
    public function execute(string $action, array $entry, string $root): string;
}
