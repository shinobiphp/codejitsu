<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Models;
use RuntimeException;
final class ModelfileRenderer
{
    public function render(string $source,string $base):string
    {
        if(preg_match('/^\/?[A-Za-z0-9][A-Za-z0-9._:\/-]*$/',$base)!==1||str_contains($base,'..'))throw new RuntimeException('Invalid model base reference.');
        $count=0;$rendered=preg_replace_callback('/^FROM\s+\S+\s*$/m',static function()use($base,&$count):string{$count++;return 'FROM '.$base;},$source);
        if($count!==1||!is_string($rendered))throw new RuntimeException('Modelfile must contain exactly one FROM directive.');
        return $rendered;
    }
}
