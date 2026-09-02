<?php declare(strict_types=1); namespace Codejitsu\Composer; use Codejitsu\Contracts\Packages\SetupIO;use Composer\IO\IOInterface;
final readonly class ComposerSetupIO implements SetupIO
{
    public function __construct(private IOInterface $io){}
    public function select(string $question,array $choices):string{$selected=$this->io->select($question,$choices);return (string)$choices[(int)$selected];}
    public function ask(string $question,string $default=''):string{return (string)$this->io->ask($question,$default);}
    public function confirm(string $question):bool{return $this->io->askConfirmation($question.' [y/N] ',false);}
    public function write(string $message):void{$this->io->write(rtrim($message));}
}
