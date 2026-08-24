<?php
declare(strict_types=1);

namespace Codejitsu\Enums\Crypto;

use Codejitsu\Traits\EnhancedEnum;

enum HashAlgorithms: string
{
    use EnhancedEnum;

    case SHA256 = 'sha256';
    case SHA384 = 'sha384';
    case SHA512 = 'sha512';
    case SHA3_256 = 'sha3-256';
    case SHA3_384 = 'sha3-384';
    case SHA3_512 = 'sha3-512';
}