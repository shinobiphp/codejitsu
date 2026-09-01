<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Commands;

use Codejitsu\Ai\Definitions\DefinitionLoader;
use Codejitsu\Ai\Neuron\ProviderFactory;
use Codejitsu\ExecutionContext;
use RuntimeException;

final class Providers
{
    public static function list(ExecutionContext $context): string
    {
        $rows = self::codex($context)->query(['type'=>'provider']);
        usort($rows, static fn ($a, $b): int => $a->name <=> $b->name);
        return $rows === [] ? "No Provider Scrolls found.\n" : implode("\n", array_map(static fn ($row): string => sprintf('%s\t%s', $row->name, $row->uri), $rows)) . "\n";
    }

    public static function show(ExecutionContext $context): string
    {
        $provider = (new DefinitionLoader(self::codex($context)))->provider(self::argument($context));
        return json_encode(['name'=>$provider->name,'adapter'=>$provider->adapter,'model'=>$provider->model,'credentials'=>$provider->credentials,'options'=>$provider->options,'metadata'=>$provider->metadata], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR) . "\n";
    }

    public static function test(ExecutionContext $context): string
    {
        $provider = (new DefinitionLoader(self::codex($context)))->provider(self::argument($context));
        $result = (new ProviderFactory())->test($provider);
        return sprintf("Provider [%s] is configured (%s / %s).\n", $provider->name, $result['adapter'], $result['model']);
    }

    private static function codex(ExecutionContext $context): \Codejitsu\Scrolls\ScrollCodex { return $context->codex ?? throw new RuntimeException('Provider commands require a bound Codex.'); }
    private static function argument(ExecutionContext $context): string { $value=is_array($context->arguments)?$context->arguments[0]??null:null; if(!is_string($value)||trim($value)==='') throw new RuntimeException('A Provider name or URI is required.'); return trim($value); }
}
