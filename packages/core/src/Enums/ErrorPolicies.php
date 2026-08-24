<?php
declare(strict_types=1);

namespace Codejitsu\Enums;

use Codejitsu\Traits\EnhancedEnum;

use Throwable;

enum ErrorPolicies: string
{
    use EnhancedEnum;
    
    case ERROR  = 'error';
    case WARN   = 'warn';
    case IGNORE = 'ignore';

    public static function map(): array
    {
        return [
            'error' => [
                '$handle' => function (Throwable|string $error): void {
                    if (is_string($error)) {
                        $error = new \RuntimeException($error);
                    } 

                    throw $error;
                },
            ],
            'warn' => [
                '$handle' => function (Throwable|string $error): void {
                    trigger_error((string) $error, E_USER_WARNING);
                },
            ],
            'ignore' => [
                '$handle' => function (Throwable|string $error): void {
                    // Silently ignore
                },
            ],
        ];
    }
}