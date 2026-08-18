<?php

declare(strict_types=1);

namespace Codejitsu\Enums\Scrolls;

use Codejitsu\Contracts\Scrolls\Scroll as ScrollContract;
use Codejitsu\Enums\Codecs;
use Codejitsu\Enums\Environment;
use Codejitsu\Scrolls\Types\App as AppScroll;
use Codejitsu\Scrolls\Types\Capability as CapabilityScroll;
use Codejitsu\Scrolls\Types\Config as ConfigScroll;
use Codejitsu\Scrolls\Types\Kata as KataScroll;
use Codejitsu\Scrolls\Types\Schema as SchemaScroll;
use Codejitsu\Scrolls\Types\Skill as SkillScroll;
use Codejitsu\Traits\EnhancedEnum;
use InvalidArgumentException;

enum Types: string
{
    use EnhancedEnum;

    case APP = 'app';
    case CAPABILITY = 'capability';
    case CONFIG = 'config';
    case KATA = 'kata';
    case SCHEMA = 'schema';
    case SKILL = 'skill';

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
            'app' => array_merge(
                [
                    'class' => AppScroll::class,
                    'plural' => 'apps',
                    'long_name' => 'application',
                    'long_plural' => 'applications',
                    'extension' => 'app',
                    'scheme' => 'app://',
                ],
                $codecConfig,
            ),

            'capability' => array_merge(
                [
                    'class' => CapabilityScroll::class,
                    'plural' => 'capabilities',
                    'long_name' => 'capability',
                    'long_plural' => 'capabilities',
                    'extension' => 'capability',
                    'scheme' => 'capability://',
                ],
                $codecConfig,
            ),

            'config' => array_merge(
                [
                    'class' => ConfigScroll::class,
                    'plural' => 'configs',
                    'long_name' => 'configuration',
                    'long_plural' => 'configurations',
                    'extension' => 'config',
                    'scheme' => 'config://',
                ],
                $codecConfig,
            ),

            'kata' => array_merge(
                [
                    'class' => KataScroll::class,
                    'plural' => 'katas',
                    'long_name' => 'kata',
                    'long_plural' => 'katas',
                    'extension' => 'kata',
                    'scheme' => 'kata://',
                ],
                $codecConfig,
            ),

            'schema' => array_merge(
                [
                    'class' => SchemaScroll::class,
                    'plural' => 'schemas',
                    'long_name' => 'schema',
                    'long_plural' => 'schemas',
                    'extension' => 'schema',
                    'scheme' => 'schema://',
                ],
                $codecConfig,
            ),

            'skill' => array_merge(
                [
                    'class' => SkillScroll::class,
                    'plural' => 'skills',
                    'long_name' => 'skill',
                    'long_plural' => 'skills',
                    'extension' => 'skill',
                    'scheme' => 'skill://',
                ],
                $codecConfig,
            ),
        ];
    }

    public function className(): string
    {
        $class = $this->to('class');

        if (
            !is_string($class)
            || !class_exists($class)
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'No valid Scroll class mapped for type [%s].',
                    $this->value,
                ),
            );
        }

        return $class;
    }

    public function make(
        array $data = [],
    ): ScrollContract {
        $class = $this->className();

        $instance = new $class();

        if (!$instance instanceof ScrollContract) {
            throw new \UnexpectedValueException(
                sprintf(
                    'Scroll class [%s] does not implement ScrollContract.',
                    $class,
                ),
            );
        }

        if ($data !== []) {
            $instance->hydrate($data);
        }

        return $instance;
    }

    public function plural(): string
    {
        return (string) $this->to(
            'plural',
            $this->value . 's',
        );
    }

    public function extension(): string
    {
        return (string) $this->to(
            'extension',
            $this->value,
        );
    }

    public function scheme(): string
    {
        return (string) $this->to(
            'scheme',
            $this->value . '://',
        );
    }
}