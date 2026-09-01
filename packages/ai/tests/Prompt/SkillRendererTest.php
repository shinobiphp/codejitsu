<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tests\Prompt;

use Codejitsu\Ai\Definitions\SkillDefinition;
use Codejitsu\Ai\Exceptions\DefinitionException;
use Codejitsu\Ai\Prompt\SkillRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SkillRendererTest extends TestCase
{
    public function testItRendersRequiredAndDefaultInputs(): void
    {
        $skill = SkillDefinition::fromArray([
            'name' => 'review',
            'prompt' => 'Review {{subject}} with {{depth}} depth.',
            'inputs' => [
                'subject' => ['type' => 'string', 'required' => true],
                'depth' => ['type' => 'string', 'default' => 'focused'],
            ],
        ]);

        self::assertSame('Review Codejitsu with focused depth.', (new SkillRenderer())->render($skill, ['subject' => 'Codejitsu']));
    }

    #[DataProvider('invalidTemplates')]
    public function testItRejectsUnsafeOrUnknownTemplates(string $template): void
    {
        $this->expectException(DefinitionException::class);
        (new SkillRenderer())->render(SkillDefinition::fromArray([
            'name' => 'bad', 'prompt' => $template,
            'inputs' => ['subject' => ['type' => 'string', 'required' => true]],
        ]), ['subject' => 'core']);
    }

    public static function invalidTemplates(): iterable
    {
        yield ['{{subject|raw}}'];
        yield ['{{subject.name}}'];
        yield ['{{php()}}'];
        yield ['{{unknown}}'];
    }

    public function testItRejectsMissingAndUnexpectedInputs(): void
    {
        $skill = SkillDefinition::fromArray([
            'name' => 'review', 'prompt' => 'Review {{subject}}.',
            'inputs' => ['subject' => ['type' => 'string', 'required' => true]],
        ]);
        $renderer = new SkillRenderer();
        try { $renderer->render($skill, []); self::fail('Missing input accepted.'); }
        catch (DefinitionException $e) { self::assertStringContainsString('subject', $e->getMessage()); }
        $this->expectException(DefinitionException::class);
        $renderer->render($skill, ['subject' => 'core', 'extra' => 'no']);
    }
}
