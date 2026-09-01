<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Definitions;
use Codejitsu\Ai\Exceptions\DefinitionException;

final readonly class ProviderDefinition
{
    public function __construct(public string $name, public string $adapter, public string $model, public array $credentials, public array $options, public array $metadata) {}

    public static function fromArray(array $data): self
    {
        $name = DefinitionData::string($data, 'name', 'Provider', true);
        $owner = sprintf('Provider [%s]', $name);
        $credentials = $data['credentials'] ?? null;
        if (!is_array($credentials) || $credentials === []) throw new DefinitionException($owner . ' credentials must be a non-empty map of credential references.');
        foreach ($credentials as $key => $reference) {
            if (!is_string($key) || !is_string($reference) || preg_match('~^env://[A-Z][A-Z0-9_]*$~', $reference) !== 1) {
                throw new DefinitionException(sprintf('%s credential [%s] must be an env:// credential reference.', $owner, (string) $key));
            }
        }
        $options = $data['options'] ?? [];
        if (!is_array($options)) throw new DefinitionException($owner . ' options must be a map.');
        return new self($name, strtolower(DefinitionData::string($data, 'adapter', $owner, true)), DefinitionData::string($data, 'model', $owner, true), $credentials, $options, is_array($data['metadata'] ?? null) ? $data['metadata'] : []);
    }
}
