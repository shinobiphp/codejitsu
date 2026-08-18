<?php

declare(strict_types=1);

namespace Codejitsu\Discovery\Strategies;

use Codejitsu\Attributes\Discoverable;
use Codejitsu\Contracts\Discovery\Strategy as StrategyContract;
use ReflectionClass;

final class Attribute implements StrategyContract
{
    public function matches(ReflectionClass $reflection, array $params = []): bool
    {
        $targetAttribute = $params['attribute'] ?? Discoverable::class;
        $attributes = $reflection->getAttributes($targetAttribute);

        if ($attributes === []) {
            return false;
        }

        if (isset($params['group'])) {
            $matchedGroup = false;
            foreach ($attributes as $attr) {
                $instance = $attr->newInstance();
                if (property_exists($instance, 'group') && $instance->group === $params['group']) {
                    $matchedGroup = true;
                    break;
                }
            }
            if (!$matchedGroup) {
                return false;
            }
        }

        if (isset($params['tag'])) {
            $matchedTag = false;
            foreach ($attributes as $attr) {
                $instance = $attr->newInstance();
                if (property_exists($instance, 'tags') && in_array($params['tag'], (array) $instance->tags, true)) {
                    $matchedTag = true;
                    break;
                }
            }
            if (!$matchedTag) {
                return false;
            }
        }

        return true;
    }
}