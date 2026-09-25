<?php
declare(strict_types=1);
namespace Codejitsu\Apps;
final readonly class EffectiveApplication {
 /** @param array<string,mixed> $data @param list<string> $inheritance */
 public function __construct(public string $uri, public array $data, public array $inheritance=[], public array $provenance=[]) {}
 public function spec(): ?string { $v=$this->data['spec']??null; return is_string($v)&&trim($v)!==''?trim($v):null; }
}