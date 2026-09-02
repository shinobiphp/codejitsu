<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Commands;

use RuntimeException;

final readonly class AiRunOptions
{
    public function __construct(
        public string $vessel,
        public string $prompt,
        public ?string $spark,
        public ?string $provider,
        public ?string $model,
        public array $skills,
        public array $skillInputs,
        public array $approvedTools,
    ) {}

    public static function parse(array $arguments): self
    {
        $vessel = array_shift($arguments);
        if (!is_string($vessel) || trim($vessel) === '') throw new RuntimeException('A Vessel name or URI is required.');
        $values = ['spark' => null, 'provider' => null, 'model' => null];
        $approved = [];
        $skills = [];
        $skillInputs = [];
        $prompt = [];
        for ($index = 0; $index < count($arguments); $index++) {
            $argument = $arguments[$index];
            if (!is_string($argument)) continue;
            if (str_starts_with($argument, '--approve-tool=')) {
                $approved[] = self::optionValue($argument, '--approve-tool=');
                continue;
            }
            if (str_starts_with($argument, '--skill=')) {
                $skills[] = self::optionValue($argument, '--skill=');
                continue;
            }
            if ($argument === '--input' || str_starts_with($argument, '--input=')) {
                $input = $argument === '--input'
                    ? ($arguments[++$index] ?? throw new RuntimeException('Option [--input] requires a query string.'))
                    : self::optionValue($argument, '--input=');
                if (!is_string($input) || trim($input) === '') throw new RuntimeException('Option [--input] requires a query string.');
                if ($skills === []) throw new RuntimeException('Option [--input] requires a preceding [--skill].');
                foreach (explode('&', $input) as $pair) {
                    [$name, $value] = array_pad(explode('=', $pair, 2), 2, null);
                    $name = urldecode(trim($name));
                    if (!is_string($value) || $name === '') throw new RuntimeException('Option [--input] must use a name=value query string.');
                    $skillInputs[$skills[array_key_last($skills)]][$name] = urldecode($value);
                }
                continue;
            }
            $matched = false;
            foreach (array_keys($values) as $name) {
                $prefix = '--' . $name . '=';
                if (!str_starts_with($argument, $prefix)) continue;
                $values[$name] = self::optionValue($argument, $prefix);
                $matched = true;
                break;
            }
            if ($matched) continue;
            if (str_starts_with($argument, '--')) throw new RuntimeException(sprintf('Unknown ai:run option [%s].', $argument));
            $prompt[] = $argument;
        }
        if ($prompt === []) throw new RuntimeException('A prompt is required.');
        return new self(trim($vessel), implode(' ', $prompt), $values['spark'], $values['provider'], $values['model'], array_values(array_unique($skills)), $skillInputs, array_values(array_unique($approved)));
    }

    private static function optionValue(string $argument, string $prefix): string
    {
        $value = trim(substr($argument, strlen($prefix)));
        if ($value === '') throw new RuntimeException(sprintf('Option [%s] requires a value.', rtrim($prefix, '=')));
        return $value;
    }
}
