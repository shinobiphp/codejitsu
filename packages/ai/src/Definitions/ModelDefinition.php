<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Definitions;
use Codejitsu\Ai\Exceptions\DefinitionException;

final readonly class ModelDefinition
{
    public function __construct(public string $name,public string $runtime,public string $destination,public string $base,public ?string $size,public array $capabilities,public string $modelfile,public string $storage,public array $metadata) {}
    public static function fromArray(array $data): self
    {
        $name=DefinitionData::string($data,'name','Model',true);$owner=sprintf('Model [%s]',$name);
        $runtime=strtolower(DefinitionData::string($data,'runtime',$owner,true));
        if($runtime!=='ollama')throw new DefinitionException($owner.' runtime must be [ollama].');
        $destination=DefinitionData::string($data,'destination',$owner,true);
        if(preg_match('/^[a-z0-9][a-z0-9._\/-]*(?::[a-z0-9][a-z0-9._-]*)?$/',$destination)!==1)throw new DefinitionException($owner.' destination is invalid.');
        $base=DefinitionData::string($data,'base',$owner,true);
        if(preg_match('/^[A-Za-z0-9][A-Za-z0-9._:\/-]*$/',$base)!==1||str_contains($base,'..'))throw new DefinitionException($owner.' base reference is invalid.');
        $modelfile=self::relative(DefinitionData::string($data,'modelfile',$owner,true),$owner.' modelfile');
        $storage=self::relative(DefinitionData::string($data,'storage',$owner,true),$owner.' storage');
        return new self($name,$runtime,$destination,$base,DefinitionData::string($data,'size',$owner),DefinitionData::strings($data,'capabilities',$owner),$modelfile,$storage,is_array($data['metadata']??null)?$data['metadata']:[]);
    }
    private static function relative(string $path,string $field): string
    {
        $path=str_replace('\\','/',trim($path));
        if(str_starts_with($path,'/')||preg_match('/^[A-Za-z]:\//',$path)===1||in_array('..',explode('/',$path),true))throw new DefinitionException($field.' must be a safe relative path.');
        return trim($path,'/');
    }
}
