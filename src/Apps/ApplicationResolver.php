<?php
declare(strict_types=1);
namespace Codejitsu\Apps;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\Types\App;
use Codejitsu\Scrolls\Types\Spec;
use InvalidArgumentException;
final readonly class ApplicationResolver {
 public function __construct(private ScrollCodex $codex,private string $defaultSpec='spec://shinobi/app') {}
 public function resolve(string|App $app): EffectiveApplication {
  $effective=(new ApplicationComposer($this->codex))->compose($app);
  $specUri=$effective->spec()??$this->defaultSpec;
  $spec=$this->codex->resolve($specUri);
  if(!$spec instanceof Spec) throw new InvalidArgumentException(sprintf('[%s] does not resolve to a Spec Scroll.',$specUri));
  $subject=(new App())->hydrate($effective->data)->bind($this->codex);
  $spec->bind($this->codex)($subject)->assertValid($effective->uri);
  return $effective;
 }
}