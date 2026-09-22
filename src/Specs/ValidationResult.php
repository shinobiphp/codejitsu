<?php
declare(strict_types=1);
namespace Codejitsu\Specs;
use RuntimeException;
final readonly class ValidationResult {
 /** @param list<ValidationFailure> $failures */
 public function __construct(private array $failures=[]) {}
 public function valid(): bool { return $this->failures===[]; }
 /** @return list<ValidationFailure> */ public function failures(): array { return $this->failures; }
 public function assertValid(string $subject='resource'): void {
  if ($this->valid()) return;
  $lines=array_map(static fn(ValidationFailure $f): string => sprintf('[%s] %s',$f->path,$f->message),$this->failures);
  throw new RuntimeException(sprintf("%s does not conform:\n%s",$subject,implode("\n",$lines)));
 }
}