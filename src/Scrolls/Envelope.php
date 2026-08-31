<?php

declare(strict_types=1);

namespace Codejitsu\Scrolls;

use Codejitsu\Contracts\Crypto\Seal as SealContract;
use Codejitsu\Contracts\Crypto\Signature as SignatureContract;
use Codejitsu\Contracts\Scrolls\Envelope as EnvelopeContract;
use Codejitsu\Enums\Codecs as Codec;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use Codejitsu\Metadata;

final class Envelope
    extends \Codejitsu\Envelope
    implements EnvelopeContract
{
    public function __construct(
        string $name,
        public string $version,
        public ScrollTypes|string $scrollType,
        string $data,
        Metadata $metadata,
        ?SealContract $seal = null,
        ?SignatureContract $signature = null,
        Codec $codec = Codec::NEON,
    ) {
        parent::__construct(
            name: $name,
            data: $data,
            metadata: $metadata,
            seal: $seal,
            signature: $signature,
            codec: $codec,
        );
    }
}
