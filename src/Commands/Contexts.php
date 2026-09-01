<?php
declare(strict_types=1);
namespace Codejitsu\Commands;

use Codejitsu\Context\ContextMemory;
use Codejitsu\Context\ContextTui;
use Codejitsu\Console\TerminalEditor;
use Codejitsu\Console\TerminalQuestioner;
use Codejitsu\ExecutionContext;
use RuntimeException;

final class Contexts
{
    public static function list(ExecutionContext $context): string
    {
        return self::rows(self::memory($context)->list());
    }

    public static function show(ExecutionContext $context): string
    {
        return self::memory($context)->show(self::argument($context, 0, 'A Context name or URI is required.'));
    }

    public static function search(ExecutionContext $context): string
    {
        return self::rows(self::memory($context)->search(self::argument($context, 0, 'A Context search query is required.')));
    }

    public static function check(ExecutionContext $context): string
    {
        $errors = self::memory($context)->check();
        return $errors === [] ? "Context memory is valid.\n" : implode("\n", $errors) . "\n";
    }

    public static function sync(ExecutionContext $context): string
    {
        $section = self::argument($context, 0, 'A managed section is required.');
        $content = self::argument($context, 1, 'Managed section content is required.');
        return sprintf("Updated %d Context Scroll(s).\n", self::memory($context)->sync($section, $content));
    }

    public static function resume(ExecutionContext $context): string
    {
        return self::memory($context)->resume();
    }

    public static function tui(ExecutionContext $context): string
    {
        return (new ContextTui(self::memory($context)))->run(new TerminalQuestioner(), new TerminalEditor());
    }

    public static function create(ExecutionContext $context): string
    {
        $name = self::argument($context, 0, 'A Context name is required.');
        self::memory($context)->create($name, new TerminalEditor());
        return sprintf("Created Context Scroll [%s].\n", $name);
    }

    public static function edit(ExecutionContext $context): string
    {
        $name = self::argument($context, 0, 'A Context name is required.');
        self::memory($context)->edit($name, new TerminalEditor());
        return sprintf("Updated Context Scroll [%s].\n", $name);
    }

    private static function memory(ExecutionContext $context): ContextMemory
    {
        if ($context->codex === null) throw new RuntimeException('Context commands require a bound ScrollCodex.');
        $root = getcwd() ?: throw new RuntimeException('Unable to determine the project root.');
        return new ContextMemory($context->codex, $root . '/.context');
    }

    private static function argument(ExecutionContext $context, int $index, string $message): string
    {
        $value = trim((string) ($context->arguments[$index] ?? ''));
        if ($value === '') throw new RuntimeException($message);
        return $value;
    }

    /** @param list<array{name:string,uri:string,source:string,tags:array}> $rows */
    private static function rows(array $rows): string
    {
        if ($rows === []) return "No Context Scrolls found.\n";
        return implode('', array_map(static fn (array $row): string => sprintf("%-32s %s\n", $row['name'], $row['uri']), $rows));
    }
}
