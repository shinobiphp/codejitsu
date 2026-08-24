<?php
declare(strict_types=1);

namespace Codejitsu\Enums\Identity;

enum Types: string
{
    case Intent = 'intent';
    case Request = 'request';
    case Scroll = 'scroll';
}