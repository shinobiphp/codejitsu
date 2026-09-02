<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Commands;

use Codejitsu\Ai\Definitions\DefinitionLoader;
use Codejitsu\Ai\Prompt\SkillRenderer;
use Codejitsu\ExecutionContext;
use RuntimeException;

final class AiResources
{
    public static function sparkList(ExecutionContext $c): string { return self::listing($c, 'spark'); }
    public static function vesselList(ExecutionContext $c): string { return self::listing($c, 'vessel'); }
    public static function skillList(ExecutionContext $c): string { return self::listing($c, 'skill'); }
    public static function toolList(ExecutionContext $c): string { return self::listing($c, 'tool'); }
    public static function toolsetList(ExecutionContext $c): string { return self::listing($c, 'toolset'); }
    public static function sparkShow(ExecutionContext $c): string { return self::show($c, 'spark'); }
    public static function vesselShow(ExecutionContext $c): string { return self::show($c, 'vessel'); }
    public static function skillShow(ExecutionContext $c): string { return self::show($c, 'skill'); }
    public static function toolShow(ExecutionContext $c): string { return self::show($c, 'tool'); }
    public static function toolsetShow(ExecutionContext $c): string { return self::show($c, 'toolset'); }

    public static function skillRender(ExecutionContext $c): string
    {
        $loader = new DefinitionLoader(self::codex($c));
        $arguments = is_array($c->arguments) ? array_values($c->arguments) : [];
        $reference = self::argument($arguments, 0);
        $inputs = [];
        foreach (array_slice($arguments, 1) as $argument) {
            if (!is_string($argument) || !str_starts_with($argument, '--input=') || !str_contains(substr($argument, 8), '=')) throw new RuntimeException('Skill inputs must use --input=name=value.');
            [$name,$value] = explode('=', substr($argument, 8), 2);
            if (isset($inputs[$name])) throw new RuntimeException(sprintf('Duplicate Skill input [%s].', $name));
            $inputs[$name] = $value;
        }
        return (new SkillRenderer())->render($loader->skill($reference), $inputs) . "\n";
    }

    private static function listing(ExecutionContext $c, string $type): string
    {
        $rows = self::codex($c)->query(['type'=>$type]);
        usort($rows, fn ($a,$b) => $a->name <=> $b->name);
        return $rows === [] ? sprintf("No %s Scrolls found.\n", ucfirst($type)) : implode("\n", array_map(fn ($e) => sprintf("%s\t%s", $e->name, $e->uri), $rows)) . "\n";
    }
    private static function show(ExecutionContext $c, string $type): string
    {
        $arguments = is_array($c->arguments) ? array_values($c->arguments) : [];
        $reference = self::argument($arguments, 0);
        $codex = self::codex($c);
        if (!str_contains($reference, '://')) { $matches=$codex->query(['type'=>$type,'name'=>$reference]); if(count($matches)!==1) throw new RuntimeException(sprintf('%s [%s] was not found or is ambiguous.', ucfirst($type),$reference)); $reference=(string)$matches[0]->uri; }
        $scroll=$codex->resolve($reference); if($scroll===null) throw new RuntimeException(sprintf('%s [%s] was not found.',ucfirst($type),$reference));
        return json_encode($scroll->toArray(), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR) . "\n";
    }
    private static function codex(ExecutionContext $c): \Codejitsu\Scrolls\ScrollCodex { return $c->codex ?? throw new RuntimeException('AI commands require a bound Codex.'); }
    private static function argument(array $arguments,int $index): string { $v=$arguments[$index]??null; if(!is_string($v)||trim($v)==='') throw new RuntimeException('A resource name or URI is required.'); return trim($v); }
}
