<?php declare(strict_types=1); namespace Codejitsu\Contracts\Packages; use Codejitsu\Packages\SetupContext;
interface PackageSetup { public function run(SetupContext $context):int; }
