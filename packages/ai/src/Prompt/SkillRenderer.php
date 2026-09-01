<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Prompt;

use Codejitsu\Ai\Definitions\SkillDefinition;
use Codejitsu\Ai\Exceptions\DefinitionException;

final class SkillRenderer
{
    public function render(SkillDefinition $skill, array $inputs): string
    {
        foreach (array_keys($inputs) as $name) {
            if (!is_string($name) || !array_key_exists($name, $skill->inputs)) {
                throw new DefinitionException(sprintf('Skill [%s] received unknown input [%s].', $skill->name, (string) $name));
            }
        }

        $values = [];
        foreach ($skill->inputs as $name => $definition) {
            if (!is_string($name) || !is_array($definition)) {
                throw new DefinitionException(sprintf('Skill [%s] has an invalid input definition.', $skill->name));
            }
            $value = $inputs[$name] ?? ($definition['default'] ?? null);
            if ($value === null && ($definition['required'] ?? false) === true) {
                throw new DefinitionException(sprintf('Skill [%s] requires input [%s].', $skill->name, $name));
            }
            if ($value !== null) $values[$name] = $this->value($skill->name, $name, $definition['type'] ?? 'string', $value);
        }

        $rendered = preg_replace_callback('/{{\s*([a-z][a-z0-9_-]*)\s*}}/i', function (array $match) use ($skill, $values): string {
            if (!array_key_exists($match[1], $skill->inputs) || !array_key_exists($match[1], $values)) {
                throw new DefinitionException(sprintf('Skill [%s] has unknown or missing template input [%s].', $skill->name, $match[1]));
            }
            return $values[$match[1]];
        }, $skill->prompt);

        if (!is_string($rendered) || str_contains($rendered, '{{') || str_contains($rendered, '}}')) {
            throw new DefinitionException(sprintf('Skill [%s] contains an invalid template expression.', $skill->name));
        }
        return $rendered;
    }

    private function value(string $skill, string $name, mixed $type, mixed $value): string
    {
        $valid = match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            default => false,
        };
        if (!$valid) throw new DefinitionException(sprintf('Skill [%s] input [%s] is not a valid %s.', $skill, $name, (string) $type));
        return is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
    }
}
