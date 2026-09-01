<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Commands;

use Codejitsu\Ai\Scaffolding\AiScaffolder;
use Codejitsu\Console\TerminalQuestioner;
use Codejitsu\ExecutionContext;
use RuntimeException;

final class MakeAi
{
    public static function spark(ExecutionContext $c): string { return self::made('Spark', self::scaffolder($c)->spark(self::value($c,0,'Spark name: '), self::value($c,1,'Instructions: '))); }
    public static function vessel(ExecutionContext $c): string { return self::made('Vessel', self::scaffolder($c)->vessel(self::value($c,0,'Vessel name: '), self::value($c,1,'Runtime: ','neuron'), self::value($c,2,'Spark: '), self::value($c,3,'Provider: '))); }
    public static function skill(ExecutionContext $c): string { return self::made('Skill', self::scaffolder($c)->skill(self::value($c,0,'Skill name: '), self::value($c,1,'Prompt: '))); }
    public static function tool(ExecutionContext $c): string { return self::made('Tool', self::scaffolder($c)->tool(self::value($c,0,'Tool name: '), self::value($c,1,'Description: '), self::value($c,2,'Capability: '), ['type'=>'object','additionalProperties'=>false,'properties'=>[]])); }
    public static function toolset(ExecutionContext $c): string
    {
        $arguments = is_array($c->arguments) ? array_values($c->arguments) : [];
        $tools = array_values(array_filter(array_slice($arguments, 1), fn ($v) => is_string($v) && trim($v) !== ''));
        return self::made('Toolset', self::scaffolder($c)->toolset(self::value($c,0,'Toolset name: '), $tools));
    }
    public static function provider(ExecutionContext $c): string { return self::made('Provider', self::scaffolder($c)->provider(self::value($c,0,'Provider name: '), self::value($c,1,'Adapter: ','openai'), self::value($c,2,'Model: '), self::value($c,3,'API key reference: ','env://OPENAI_API_KEY'))); }
    private static function scaffolder(ExecutionContext $c): AiScaffolder
    {
        if ($c->codex === null) throw new RuntimeException('AI makers require a bound Codex.');
        return new AiScaffolder(getcwd() ?: throw new RuntimeException('Project root unavailable.'), $c->codex->types());
    }
    private static function value(ExecutionContext $c, int $index, string $question, string $default=''): string
    {
        $value = is_array($c->arguments) ? $c->arguments[$index] ?? null : null;
        return is_string($value) && trim($value) !== '' ? trim($value) : (new TerminalQuestioner())->ask($question, $default);
    }
    private static function made(string $type, string $path): string { return sprintf("Created %s Scroll [%s].\n", $type, $path); }
}
