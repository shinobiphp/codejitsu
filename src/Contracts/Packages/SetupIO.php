<?php declare(strict_types=1); namespace Codejitsu\Contracts\Packages;
interface SetupIO { public function select(string $question,array $choices):string;public function ask(string $question,string $default=''):string;public function confirm(string $question):bool;public function write(string $message):void; }
