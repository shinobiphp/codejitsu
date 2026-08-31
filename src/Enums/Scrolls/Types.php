<?php

declare(strict_types=1);

namespace Codejitsu\Enums\Scrolls;

use Codejitsu\Contracts\Scrolls\Envelope as EnvelopeContract;
use Codejitsu\Contracts\Scrolls\Scroll as ScrollContract;
use Codejitsu\Enums\Codecs;
use Codejitsu\Scrolls\Types\App as AppScroll;
use Codejitsu\Scrolls\Types\Capability as CapabilityScroll;
use Codejitsu\Scrolls\Types\Command as CommandScroll;
use Codejitsu\Scrolls\Types\Config as ConfigScroll;
use Codejitsu\Scrolls\Types\Context as ContextScroll;
use Codejitsu\Scrolls\Types\Kata as KataScroll;
use Codejitsu\Scrolls\Types\Package as PackageScroll;
use Codejitsu\Scrolls\Types\Schema as SchemaScroll;
use Codejitsu\Scrolls\Types\Skill as SkillScroll;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Traits\EnhancedEnum;

enum Types: string
{
    use EnhancedEnum;

    case APP = 'app';
    case CAPABILITY = 'capability';
    case COMMAND = 'command';
    case CONFIG = 'config';
    case CONTEXT = 'context';
    case KATA = 'kata';
    case PACKAGE = 'package';
    case SCHEMA = 'schema';
    case SKILL = 'skill';

    public static function normalize(mixed $value, self|string|null $default = null, bool $passthroughUnmatched = false): self|string|null
    {
        if ($value instanceof self) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));
        $normalized = match ($normalized) {
            'cmd' => 'command',
            default => $normalized,
        };

        $match = self::tryFrom($normalized);
        if ($match instanceof self) {
            return $match;
        }

        if ($passthroughUnmatched) {
            return $value;
        }

        return $default instanceof self
            ? $default
            : (is_string($default) ? self::tryFrom($default) : null);
    }

    public static function map(): array
    {
        $codec = Codecs::default();
        $codecConfig = [
            'codec' => $codec,
            'signer' => $codec->to('signer'),
            'sealer' => $codec->to('sealer'),
            '$signer' => $codec->to('$signer'),
            '$sealer' => $codec->to('$sealer'),
            '$onUnsigned' => $codec->to('onUnsigned'),
            '$onInvalidSignature' => $codec->to('onInvalidSig'),
        ];

        return [
            'app' => array_merge(['class' => AppScroll::class, 'plural' => 'apps', 'long_name' => 'application', 'long_plural' => 'applications', 'extension' => 'app', 'scheme' => 'app://'], $codecConfig),
            'capability' => array_merge(['class' => CapabilityScroll::class, 'plural' => 'capabilities', 'long_name' => 'capability', 'long_plural' => 'capabilities', 'extension' => 'capability', 'scheme' => 'capability://'], $codecConfig),
            'command' => array_merge(['class' => CommandScroll::class, 'plural' => 'commands', 'long_name' => 'command', 'long_plural' => 'commands', 'extension' => 'cmd', 'scheme' => 'cmd://'], $codecConfig),
            'config' => array_merge(['class' => ConfigScroll::class, 'plural' => 'configs', 'long_name' => 'configuration', 'long_plural' => 'configurations', 'extension' => 'config', 'scheme' => 'config://'], $codecConfig),
            'context' => array_merge(['class' => ContextScroll::class, 'plural' => 'contexts', 'long_name' => 'context', 'long_plural' => 'contexts', 'extension' => 'ctx', 'scheme' => 'context://'], $codecConfig),
            'kata' => array_merge(['class' => KataScroll::class, 'plural' => 'katas', 'long_name' => 'kata', 'long_plural' => 'katas', 'extension' => 'kata', 'scheme' => 'kata://'], $codecConfig),
            'package' => array_merge(['class' => PackageScroll::class, 'plural' => 'packages', 'long_name' => 'package', 'long_plural' => 'packages', 'extension' => 'package', 'scheme' => 'package://'], $codecConfig),
            'schema' => array_merge(['class' => SchemaScroll::class, 'plural' => 'schemas', 'long_name' => 'schema', 'long_plural' => 'schemas', 'extension' => 'schema', 'scheme' => 'schema://'], $codecConfig),
            'skill' => array_merge(['class' => SkillScroll::class, 'plural' => 'skills', 'long_name' => 'skill', 'long_plural' => 'skills', 'extension' => 'skill', 'scheme' => 'skill://'], $codecConfig),
        ];
    }

    public function className(): string
    {
        return match ($this) {
            self::APP => AppScroll::class,
            self::CAPABILITY => CapabilityScroll::class,
            self::COMMAND => CommandScroll::class,
            self::CONFIG => ConfigScroll::class,
            self::CONTEXT => ContextScroll::class,
            self::KATA => KataScroll::class,
            self::PACKAGE => PackageScroll::class,
            self::SCHEMA => SchemaScroll::class,
            self::SKILL => SkillScroll::class,
        };
    }

    public function make(?EnvelopeContract $envelope = null, array $data = []): ScrollContract
    {
        $class = $this->className();
        $instance = $envelope === null ? new $class() : $class::make($envelope, $data);

        if (!$instance instanceof ScrollContract) {
            throw new \UnexpectedValueException(sprintf('Scroll class [%s] does not implement ScrollContract.', $class));
        }

        if ($envelope === null && $data !== []) {
            $instance->hydrate($data);
        }

        return $instance;
    }

    public function plural(): string
    {
        return match ($this) {
            self::APP => 'apps',
            self::CAPABILITY => 'capabilities',
            self::COMMAND => 'commands',
            self::CONFIG => 'configs',
            self::CONTEXT => 'contexts',
            self::KATA => 'katas',
            self::PACKAGE => 'packages',
            self::SCHEMA => 'schemas',
            self::SKILL => 'skills',
        };
    }

    public function extension(): string
    {
        return match ($this) {
            self::COMMAND => 'cmd',
            self::APP => 'app',
            self::CAPABILITY => 'capability',
            self::CONFIG => 'config',
            self::CONTEXT => 'ctx',
            self::KATA => 'kata',
            self::PACKAGE => 'package',
            self::SCHEMA => 'schema',
            self::SKILL => 'skill',
        };
    }

    public function scheme(): string
    {
        return ($this === self::COMMAND ? 'cmd' : $this->value) . '://';
    }

    public function definition(): TypeDefinition
    {
        return new TypeDefinition(
            $this->value,
            $this->plural(),
            $this->extension(),
            $this->scheme(),
            $this->className(),
            Codecs::NEON,
        );
    }
}
