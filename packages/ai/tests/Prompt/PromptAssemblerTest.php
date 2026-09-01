<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tests\Prompt;

use Codejitsu\Ai\Definitions\SkillDefinition;
use Codejitsu\Ai\Definitions\SparkDefinition;
use Codejitsu\Ai\Definitions\ToolsetDefinition;
use Codejitsu\Ai\Prompt\PromptAssembler;
use Codejitsu\Ai\Prompt\SkillRenderer;
use PHPUnit\Framework\TestCase;

final class PromptAssemblerTest extends TestCase
{
    public function testItPreservesDeterministicCompositionOrder(): void
    {
        $spark = SparkDefinition::fromArray(['name' => 'architect', 'instructions' => 'Be precise.']);
        $skills = [
            SkillDefinition::fromArray(['name' => 'review', 'prompt' => 'Review {{subject}}.', 'inputs' => ['subject' => ['required' => true, 'type' => 'string']]]),
            SkillDefinition::fromArray(['name' => 'simplify', 'prompt' => 'Prefer simplicity.']),
        ];
        $toolsets = [ToolsetDefinition::fromArray(['name' => 'context', 'tools' => ['tool://context.show'], 'guidelines' => 'Read before answering.'])];

        $result = (new PromptAssembler(new SkillRenderer()))->assemble(
            $spark, $skills, ['review' => ['subject' => 'Codejitsu']],
            ['context://architecture' => 'Architecture notes.', 'context://rules' => 'Project rules.'],
            $toolsets, 'What should change?',
        );

        self::assertSame("Be precise.\n\nReview Codejitsu.\n\nPrefer simplicity.\n\nRead before answering.", $result->instructions);
        self::assertSame("Architecture notes.\n\nProject rules.", $result->context);
        self::assertSame('What should change?', $result->userInput);
        self::assertSame(['review', 'simplify'], $result->skills);
    }
}
