<?php

declare(strict_types=1);

namespace Codejitsu\Discovery;

class Discoverer
{
    public function extractFullyQualifiedClassName(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $tokens = token_get_all($content);
        $namespace = '';
        $class = '';
        
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                $i++;
                while ($i < $count && $tokens[$i] !== ';') {
                    if (is_array($tokens[$i])) {
                        if (in_array($tokens[$i][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                            $namespace .= $tokens[$i][1];
                        }
                    }
                    $i++;
                }
            }

            if ($tokens[$i][0] === T_CLASS) {
                $i++;
                while ($i < $count && $tokens[$i][0] !== T_STRING) {
                    $i++;
                }
                if ($i < $count && isset($tokens[$i][1])) {
                    $class = $tokens[$i][1];
                    break;
                }
            }
        }

        if ($namespace && $class) {
            return $namespace . '\\' . $class;
        }

        return $class ?: null;
    }
}