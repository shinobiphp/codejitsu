<?php
declare(strict_types=1);
namespace Codejitsu\Console;

use Codejitsu\Scrolls\Types\Command;
use RuntimeException;

final class CommandRegistry
{
    /** @param iterable<Command> $commands @return list<Command> */
    public function merge(iterable $commands): array
    {
        $merged=[];
        foreach($commands as $command){
            if(!isset($merged[$command->name])){$merged[$command->name]=clone $command;continue;}
            $existing=$merged[$command->name];
            if(!$existing->isNamespace()||!$command->isNamespace()) throw new RuntimeException(sprintf('Command [%s] is declared more than once.', $command->name));
            $children=$existing->commands();
            foreach($command->commands() as $name=>$definition){
                if(isset($children[$name])) throw new RuntimeException(sprintf('Command [%s:%s] is declared more than once.', $command->name,$name));
                $children[$name]=$definition;
            }
            $existing->hydrate([...$existing->toArray(),'commands'=>$children]);
        }
        foreach($merged as $command){
            if(!$command->isNamespace())continue;
            $children=$command->commands();
            uksort($children,'strnatcasecmp');
            $command->hydrate([...$command->toArray(),'commands'=>$children]);
        }
        uksort($merged,'strnatcasecmp');
        return array_values($merged);
    }
}
