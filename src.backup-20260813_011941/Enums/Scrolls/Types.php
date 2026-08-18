<?php
declare(strict_types=1);

namespace Codejitsu\Enums\Scrolls;

use Codejitsu\Scrolls\Types\App as AppScroll;
use Codejitsu\Scrolls\Types\Capability as CapabilityScroll;
use Codejitsu\Scrolls\Types\Config as ConfigScroll;
use Codejitsu\Scrolls\Types\Kata as KataScroll;
use Codejitsu\Scrolls\Types\Schema as SchemaScroll;
use Codejitsu\Scrolls\Types\Skill as SkillScroll;

use Codejitsu\Enums\Codecs;
use Codejitsu\Enums\Environment;
use Codejitsu\Traits\EnhancedEnum;

enum Types: string
{
    use EnhancedEnum;

    case APP        = 'app';
    case CAPABILITY = 'capability';
    case CONFIG     = 'config';
    case KATA       = 'kata';
    case SCHEMA     = 'schema';
    case SKILL      = 'skill';

    public static function map(): array
    {
        $codecEnum = Codecs::default();

        $codecConfig = [
            'codec'                => $codecEnum,
            'signer'               => $codecEnum->to('signer'),
            'sealer'               => $codecEnum->to('sealer'),
            '$signer'              => $codecEnum->to('$signer'),
            '$sealer'              => $codecEnum->to('$sealer'),
            '$onUnsigned'          => $codecEnum->to('onUnsigned'),
            '$onInvalidSignature'  => $codecEnum->to('onInvalidSig'),
        ];

        return [
            'app'        => array_merge(['class' => AppScroll::class, 'plural' => 'apps', 'long_name' => 'application', 'long_plural' => 'applications', 'extension' => 'app', 'scheme' => 'app://'], $codecConfig),
            'capability' => array_merge(['class' => CapabilityScroll::class, 'plural' => 'capabilities', 'long_name' => 'capability', 'long_plural' => 'capabilities', 'extension' => 'capability', 'scheme' => 'capability://'], $codecConfig),
            'config'     => array_merge(['class' => ConfigScroll::class, 'plural' => 'configs', 'long_name' => 'configuration', 'long_plural' => 'configurations', 'extension' => 'config', 'scheme' => 'config://'], $codecConfig),
            'kata'       => array_merge(['class' => KataScroll::class, 'plural' => 'katas', 'long_name' => 'kata', 'long_plural' => 'katas', 'extension' => 'kata', 'scheme' => 'kata://'], $codecConfig),
            'schema'     => array_merge(['class' => SchemaScroll::class, 'plural' => 'schemas', 'long_name' => 'schema', 'long_plural' => 'schemas', 'extension' => 'schema', 'scheme' => 'schema://'], $codecConfig),
            'skill'      => array_merge(['class' => SkillScroll::class, 'plural' => 'skills', 'long_name' => 'skill', 'long_plural' => 'skills', 'extension' => 'skill', 'scheme' => 'skill://'], $codecConfig),
        ];
    }

    public function make(array $data = []): mixed
    {
        $class = $this->to('class');
        
        if (!$class || !class_exists($class)) {
            $env = Environment::current();
            return $env->to('$onError')(
                new \InvalidArgumentException("No valid scroll class mapped for type: {$this->value}")
            );
        }

        $instance = new $class();

        if (!empty($data) && method_exists($instance, 'hydrate')) {
            $instance->hydrate($data);
        }

        return $instance;
    }
}