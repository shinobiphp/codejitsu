<?php
declare(strict_types=1);
namespace Codejitsu\Data\Compiler;
final readonly class CompiledEntity{public function __construct(public string $className,public string $php,public array $manifest){}}
