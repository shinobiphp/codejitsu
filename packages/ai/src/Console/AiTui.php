<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Console;

use Codejitsu\Ai\Definitions\DefinitionLoader;
use Codejitsu\Ai\Tools\InteractiveToolApproval;
use Codejitsu\Ai\Vessels\VesselRunner;
use Codejitsu\Scrolls\ScrollCodex;

final readonly class AiTui
{
    public function __construct(private VesselRunner $runner, private ScrollCodex $codex) {}

    public function run(ConversationIO $io): int
    {
        $vessels = $this->names('vessel');
        if ($vessels === []) { $io->write("No Vessel Scrolls found.\n"); return 1; }

        $loader = new DefinitionLoader($this->codex);
        $vessel = $io->select('Vessel', $vessels);
        $definition = $loader->vessel($vessel);
        $sparks = array_map($this->shortName(...), $definition->allowedSparks);
        $spark = $io->select('Spark', array_values(array_unique($sparks)));
        $provider = $io->select('Provider', $this->names('provider'));
        $sparkDefinition = $loader->spark($spark);
        $skillChoices = ['[none]', ...array_map($this->shortName(...), $sparkDefinition->allowedSkills)];
        $skill = $io->select('Skill', array_values(array_unique($skillChoices)));
        $skills = $skill === '[none]' ? [] : [$skill];
        $skillInput = $skills === [] ? '' : $io->ask('Skill inputs (name=value&name2=value2, blank uses defaults): ');
        $skillInputs = $skills === [] || trim($skillInput) === '' ? [] : [$skill => $this->query($skillInput)];
        $modelInput = trim($io->ask('Model override (blank uses Provider/Vessel default): '));
        $model = $modelInput === '' ? null : $modelInput;
        $contextInput = trim($io->ask('Contexts (comma-separated, blank uses defaults, - uses none): '));
        $contexts = $contextInput === '' ? null : ($contextInput === '-' ? [] : $this->csv($contextInput));
        $approved = $this->csv($io->ask('Pre-approved consequential Tools (comma-separated, blank confirms each): '));
        $approval = new InteractiveToolApproval(static fn ($call): bool => in_array($call->tool->name, $approved, true) || $io->confirmTool($call));

        try {
            $session = $this->runner->start($vessel, spark: $spark, skills: $skills, skillInputs: $skillInputs, approval: $approval, provider: $provider, model: $model, contexts: $contexts);
        } catch (\Throwable $e) {
            $io->write('Error: ' . $e->getMessage() . "\n");
            return 1;
        }

        $selectedModel = $model ?? 'default';
        $io->write(sprintf("AI session ready: vessel=%s spark=%s provider=%s model=%s skills=%s. Type /help for commands.\n", $vessel, $spark, $provider, $selectedModel, $skills === [] ? 'none' : implode(',', $skills)));
        while (true) {
            $input = trim($io->ask('> '));
            if ($input === '') continue;
            if ($input === '/exit') return 0;
            if ($input === '/help') { $io->write("/clear /new /context /skills /tools /spark /vessel /provider /model /exit\n"); continue; }
            if ($input === '/clear') { $session->clear(); $io->write("History cleared.\n"); continue; }
            if ($input === '/new') { $session = $this->runner->start($vessel, spark: $spark, skills: $skills, skillInputs: $skillInputs, approval: $approval, provider: $provider, model: $model, contexts: $contexts); $io->write("New session.\n"); continue; }
            $map = ['/context' => 'contexts', '/skills' => 'skills', '/tools' => 'tools'];
            if (isset($map[$input])) { $io->write(implode("\n", $session->metadata()[$map[$input]] ?? []) . "\n"); continue; }
            if ($input === '/spark') { $io->write('Spark: ' . $spark . "\n"); continue; }
            if ($input === '/vessel') { $io->write('Vessel: ' . $vessel . "\n"); continue; }
            if ($input === '/provider') { $io->write('Provider: ' . $provider . "\n"); continue; }
            if ($input === '/model') { $io->write('Model: ' . $selectedModel . "\n"); continue; }
            try { $io->write($session->send($input)->text . "\n"); }
            catch (\Throwable $e) { $io->write('Error: ' . $e->getMessage() . "\n"); }
        }
    }

    private function names(string $type): array
    {
        $names = array_map(static fn ($row): string => (string) $row->name, $this->codex->query(['type' => $type]));
        sort($names);
        return $names;
    }

    private function shortName(string $reference): string
    {
        return str_contains($reference, '://') ? substr($reference, strpos($reference, '://') + 3) : $reference;
    }

    private function csv(string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode(',', $value)), static fn (string $item): bool => $item !== '')));
    }

    private function query(string $value): array
    {
        $result = [];
        foreach (explode('&', $value) as $pair) {
            [$name, $input] = array_pad(explode('=', $pair, 2), 2, null);
            $name = urldecode(trim($name));
            if (!is_string($input) || $name === '') throw new \RuntimeException('Skill inputs must use a name=value query string.');
            $result[$name] = urldecode($input);
        }
        return $result;
    }
}
