<?php
declare(strict_types=1);
namespace Codejitsu\Scrolls\Types;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Scrolls\Scroll;
use InvalidArgumentException;
final class Spec extends Scroll
{
    public const ScrollTypes TYPE = ScrollTypes::SPEC;
    public function subject(): ?string { $v=$this->attributes['subject']??null; return is_string($v)?trim($v):null; }
    public function schema(): ?string { $v=$this->attributes['schema']??null; return is_string($v)?trim($v):null; }
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