<?php
declare(strict_types=1);
namespace Codejitsu\Specs;
final readonly class ValidationFailure { public function __construct(public string $path, public string $message) {} }