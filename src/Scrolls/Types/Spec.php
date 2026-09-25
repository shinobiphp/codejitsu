<?php
declare(strict_types=1);

namespace Codejitsu\Scrolls\Types;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Scrolls\Scroll;

use Codejitsu\Specs\SpecValidator;

use InvalidArgumentException, LogicException;

final class Spec extends Scroll
{
    public const ScrollTypes TYPE = ScrollTypes::SPEC;
    public function subject(): ?string { $v=$this->attributes['subject']??null; return is_string($v)?trim($v):null; }
    public function schema(): ?string { $v=$this->attributes['schema']??null; return is_string($v)?trim($v):null; }
    public function __invoke(mixed ...$args): mixed
    {
        if ($this->codex === null) {
            throw new LogicException('Spec must be bound to a Codex before validation.');
        }

        $subject = $args[0] ?? null;

        if (!$subject instanceof Scroll) {
            throw new InvalidArgumentException('Spec expects the first argument to be a Scroll.');
        }

        return (new SpecValidator($this->codex))->validate($this, $subject);
    }

    public function hydrate(array $data): static
    {
        foreach (['subject','schema'] as $key) {
            if (array_key_exists($key,$data) && (!is_string($data[$key]) || trim($data[$key])==='')) {
                throw new InvalidArgumentException(sprintf('Spec %s must be a non-empty string.',$key));
            }
        }
        return parent::hydrate($data);
    }
}