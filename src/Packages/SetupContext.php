<?php declare(strict_types=1); namespace Codejitsu\Packages; use Codejitsu\Contracts\Packages\SetupIO;
final readonly class SetupContext { public function __construct(public string $projectRoot,public string $packageRoot,public string $package,public string $action,public SetupIO $io,public bool $interactive,public array $environment=[]){} }
