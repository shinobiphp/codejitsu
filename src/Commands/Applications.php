<?php
declare(strict_types=1);
namespace Codejitsu\Commands;
use Codejitsu\Apps\ApplicationResolver;
use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\Types\Spec;
use LogicException;
final class Applications {
 public static function list(ExecutionContext $context): string { return self::typedList($context,'app'); }
 public static function specs(ExecutionContext $context): string { return self::typedList($context,'spec'); }
 public static function validate(ExecutionContext $context): string {
  $uri=self::uri($context,'An App URI is required.');
  $app=(new ApplicationResolver(self::codex($context)))->resolve($uri);
  return sprintf("VALID %s\n",$app->uri);
 }
 public static function show(ExecutionContext $context): string {
  $app=(new ApplicationResolver(self::codex($context)))->resolve(self::uri($context,'An App URI is required.'));
  return sprintf("App: %s\nSpec: %s\nInheritance: %s\n\n%s\n",$app->uri,$app->spec()??'spec://shinobi/app',implode(' -> ',$app->inheritance),json_encode($app->data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
 }
 public static function validateSpec(ExecutionContext $context): string {
  $codex=self::codex($context); $specUri=self::uri($context,'A Spec URI is required.'); $subjectUri=trim((string)($context->arguments[1]??''));
  $spec=$codex->resolve($specUri); if(!$spec instanceof Spec) throw new LogicException('URI does not resolve to a Spec Scroll.');
  if($subjectUri==='') return sprintf("VALID %s\n",$specUri);
  $result=$spec->bind($codex)($codex->resolve($subjectUri)); $result->assertValid($subjectUri); return sprintf("VALID %s against %s\n",$subjectUri,$specUri);
 }
 private static function typedList(ExecutionContext $context,string $type): string {
  $entries=self::codex($context)->query(['type'=>$type]); if($entries===[]) return sprintf("No %s Scrolls are currently registered.\n",ucfirst($type));
  return implode('',array_map(static fn($e): string => sprintf("%-32s %s\n",$e->uri,$e->source),$entries));
 }
 private static function codex(ExecutionContext $context): \Codejitsu\Scrolls\ScrollCodex { return $context->codex??throw new LogicException('Command requires a ScrollCodex.'); }
 private static function uri(ExecutionContext $context,string $message): string { $uri=trim((string)($context->arguments[0]??'')); if($uri==='') throw new LogicException($message); return $uri; }
}