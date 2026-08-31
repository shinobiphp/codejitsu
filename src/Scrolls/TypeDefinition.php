<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\Contracts\Scrolls\Envelope;
use Codejitsu\Contracts\Scrolls\Scroll;
use Codejitsu\Enums\Codecs;
use Codejitsu\Contracts\Codec;
use InvalidArgumentException;
use UnexpectedValueException;

final readonly class TypeDefinition
{
    public string $name;
    public string $plural;
    public string $extension;
    public string $scheme;

    /** @param class-string<Scroll> $scrollClass */
    public function __construct(
        string $name,
        string $plural,
        string $extension,
        string $scheme,
        public string $scrollClass,
        public Codecs $codec = Codecs::NEON,
        public ?string $schemaUri = null,
    ) {
        $this->name = self::token($name, 'name');
        $this->plural = self::token($plural, 'plural');
        $this->extension = self::token(ltrim(trim($extension), '.'), 'extension');
        $this->scheme = strtolower(trim($scheme));

        if (!str_ends_with($this->scheme, '://') || !preg_match('/^[a-z][a-z0-9+.-]*:\/\/$/', $this->scheme)) {
            throw new InvalidArgumentException(sprintf('Invalid Scroll type scheme [%s].', $scheme));
        }
        if (!is_a($scrollClass, Scroll::class, true)) {
            throw new InvalidArgumentException(sprintf('Scroll class [%s] must implement %s.', $scrollClass, Scroll::class));
        }
    }

    public function make(?Envelope $envelope = null, array $data = []): Scroll
    {
        $class = $this->scrollClass;
        $scroll = $envelope === null ? new $class() : $class::make($envelope, $data);
        if (!$scroll instanceof Scroll) {
            throw new UnexpectedValueException(sprintf('Scroll class [%s] returned an invalid Scroll.', $class));
        }
        if ($envelope === null && $data !== []) {
            $scroll->hydrate($data);
        }
        return $scroll;
    }

    public function makeCodec(): Codec
    {
        return match ($this->codec) {
            Codecs::JSON => new \Codejitsu\Codecs\Json(),
            Codecs::NEON => new \Codejitsu\Codecs\Neon(),
            Codecs::PHP => new \Codejitsu\Codecs\Php(),
        };
    }

    private static function token(string $value, string $field): string
    {
        $value = strtolower(trim($value));
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $value)) {
            throw new InvalidArgumentException(sprintf('Invalid Scroll type %s [%s].', $field, $value));
        }
        return $value;
    }
}
