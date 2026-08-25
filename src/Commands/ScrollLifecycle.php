<?php

declare(strict_types=1);

namespace Codejitsu\Commands;

use Codejitsu\Crypto\Key;
use Codejitsu\Crypto\Sealers\Sodium;
use Codejitsu\Crypto\Signers\Ed25519;
use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\Lifecycle\Canonicalizer;
use Codejitsu\Scrolls\Lifecycle\Lifecycle;
use Codejitsu\Scrolls\Scroll;
use Codejitsu\Scrolls\ScrollCodex;
use InvalidArgumentException;
use LogicException;

final class ScrollLifecycle
{
    public static function sign(ExecutionContext $context): string
    {
        return (new self())->execute('sign', $context);
    }

    public static function seal(ExecutionContext $context): string
    {
        return (new self())->execute('seal', $context);
    }

    public static function unseal(ExecutionContext $context): string
    {
        return (new self())->execute('unseal', $context);
    }

    public static function verify(ExecutionContext $context): int
    {
        return (new self())->verifyAll($context) ? 0 : 1;
    }

    private function execute(string $operation, ExecutionContext $context): string
    {
        $codex = $context->codex
            ?? throw new LogicException('Scroll lifecycle command requires a ScrollCodex.');
        $lifecycle = $this->lifecycle();
        $key = $this->key();
        $scrolls = $this->select($codex, $context->arguments);

        match ($operation) {
            'sign' => $lifecycle->signAll($scrolls, $key),
            'seal' => $lifecycle->sealAll($scrolls, $key),
            'unseal' => $lifecycle->unsealAll($scrolls, $key),
            default => throw new LogicException(sprintf('Unknown lifecycle operation [%s].', $operation)),
        };

        return sprintf('%s %d Scroll(s).%s', ucfirst($operation), count($scrolls), PHP_EOL);
    }

    private function verifyAll(ExecutionContext $context): bool
    {
        $codex = $context->codex
            ?? throw new LogicException('Scroll lifecycle command requires a ScrollCodex.');
        $scrolls = $this->select($codex, $context->arguments);
        $valid = $this->lifecycle()->verifyAll($scrolls, $this->key());

        foreach ($scrolls as $scroll) {
            printf("%s %s%s", $valid ? 'OK' : 'FAIL', $scroll->name, PHP_EOL);
        }

        return $valid;
    }

    /** @return list<Scroll> */
    private function select(ScrollCodex $codex, mixed $arguments): array
    {
        $arguments = is_array($arguments) ? $arguments : [$arguments];
        $all = in_array('--all', $arguments, true);
        $target = array_values(array_filter(
            $arguments,
            static fn (mixed $argument): bool => is_string($argument) && $argument !== '--all',
        ))[0] ?? null;

        if ($all) {
            return array_values(array_filter(
                $codex->all(true),
                static fn (mixed $scroll): bool => $scroll instanceof Scroll,
            ));
        }

        if (!is_string($target) || trim($target) === '') {
            throw new InvalidArgumentException('A Scroll URI or --all is required.');
        }

        $scroll = $codex->resolve($target);
        if (!$scroll instanceof Scroll) {
            throw new LogicException(sprintf('Target [%s] is not a Scroll.', $target));
        }

        return [$scroll];
    }

    private function key(): Key
    {
        $encoded = getenv('CODEJITSU_SCROLL_KEY');
        if (!is_string($encoded) || $encoded === '') {
            throw new LogicException('CODEJITSU_SCROLL_KEY must contain a base64-encoded lifecycle key.');
        }

        $contents = base64_decode($encoded, true);
        if ($contents === false || $contents === '') {
            throw new LogicException('CODEJITSU_SCROLL_KEY is not valid base64.');
        }

        return Key::secret('env', $contents);
    }

    private function lifecycle(): Lifecycle
    {
        return new Lifecycle(
            new Canonicalizer(),
            new Ed25519(),
            new Sodium(),
        );
    }
}
