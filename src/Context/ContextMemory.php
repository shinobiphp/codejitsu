<?php
declare(strict_types=1);
namespace Codejitsu\Context;

use Codejitsu\Scrolls\IndexEntry;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\Context;
use RuntimeException;

final class ContextMemory
{
    public function __construct(private readonly ScrollCodex $codex, private readonly string $root) {}

    /** @return list<array{name:string,uri:string,source:string,tags:array}> */
    public function list(): array
    {
        $items = array_map(static fn (IndexEntry $entry): array => [
            'name' => $entry->name, 'uri' => (string) $entry->uri, 'source' => $entry->source, 'tags' => $entry->tags,
        ], $this->codex->query(['type' => 'context']));
        usort($items, static fn (array $a, array $b): int => $a['name'] <=> $b['name']);
        return $items;
    }

    public function show(string $identifier): string
    {
        if (!str_contains($identifier, '://')) {
            $matches = $this->codex->query(['type' => 'context', 'name' => $identifier]);
            if (count($matches) !== 1) throw new RuntimeException(sprintf('Context [%s] was not found or is ambiguous.', $identifier));
            $identifier = (string) $matches[0]->uri;
        }
        $context = $this->codex->resolve($identifier);
        if (!$context instanceof Context) throw new RuntimeException(sprintf('[%s] is not a Context Scroll.', $identifier));
        return $context->content();
    }

    /** @return list<array{name:string,uri:string,source:string,tags:array}> */
    public function search(string $query): array
    {
        $query = strtolower(trim($query));
        if ($query === '') return [];
        return array_values(array_filter($this->list(), fn (array $item): bool =>
            str_contains(strtolower($item['name']), $query) || str_contains(strtolower($this->show($item['uri'])), $query)
        ));
    }

    /** @return list<string> */
    public function check(): array
    {
        $errors = [];
        foreach ($this->files() as $path) {
            $content = (string) file_get_contents($path);
            preg_match_all('/\[[^]]*]\(([^)#]+)(?:#[^)]+)?\)/', $content, $matches);
            foreach ($matches[1] as $link) {
                if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $link) === 1) continue;
                if (!is_file(dirname($path) . '/' . $link)) $errors[] = sprintf('%s: missing link %s', $path, $link);
            }
            preg_match_all('/<!-- codejitsu:managed ([a-z0-9_-]+):(start|end) -->/', $content, $markers, PREG_SET_ORDER);
            $open = [];
            foreach ($markers as $marker) {
                if ($marker[2] === 'start') { if (isset($open[$marker[1]])) $errors[] = $path . ': duplicate managed start ' . $marker[1]; $open[$marker[1]] = true; }
                else { if (!isset($open[$marker[1]])) $errors[] = $path . ': unmatched managed end ' . $marker[1]; unset($open[$marker[1]]); }
            }
            foreach (array_keys($open) as $name) $errors[] = $path . ': unmatched managed start ' . $name;
        }
        return $errors;
    }

    public function sync(string $section, string $content): int
    {
        if (preg_match('/^[a-z0-9_-]+$/', $section) !== 1) throw new RuntimeException('Invalid managed section name.');
        $updated = 0;
        $start = '<!-- codejitsu:managed ' . $section . ':start -->';
        $end = '<!-- codejitsu:managed ' . $section . ':end -->';
        foreach ($this->files() as $path) {
            $source = (string) file_get_contents($path);
            $pattern = '/' . preg_quote($start, '/') . '.*?' . preg_quote($end, '/') . '/s';
            $replacement = $start . "\n" . rtrim($content) . "\n" . $end;
            $next = preg_replace($pattern, $replacement, $source, 1, $count);
            if ($count === 1 && $next !== $source) { file_put_contents($path, $next); $updated++; }
        }
        return $updated;
    }

    public function resume(): string
    {
        $sections = [];
        foreach (['current-state', 'roadmap/current', 'todo'] as $name) {
            try { $sections[] = rtrim($this->show($name)); } catch (RuntimeException) {}
        }
        return implode("\n\n---\n\n", $sections) . "\n";
    }

    /** @return list<string> */
    private function files(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) if ($file->isFile() && $file->getExtension() === 'ctx') $files[] = $file->getPathname();
        sort($files);
        return $files;
    }
}
