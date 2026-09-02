<?php

declare(strict_types=1);

namespace Codejitsu\Ai\Tests\Commands;

use Codejitsu\Ai\Commands\AiRunOptions;
use PHPUnit\Framework\TestCase;

final class AiRunOptionsTest extends TestCase
{
    public function testItParsesRuntimeOverridesWithoutAddingThemToThePrompt(): void
    {
        $options = AiRunOptions::parse([
            'cognition',
            '--spark=product-designer',
            '--provider=ollama/local',
            '--model=qwen:latest',
            '--skill=product-strategy',
            '--input',
            'additional_context=Prioritize%20recurring%20revenue.&audience=investors',
            '--approve-tool=context.update-section',
            'Design',
            'the product.',
        ]);

        self::assertSame('cognition', $options->vessel);
        self::assertSame('product-designer', $options->spark);
        self::assertSame('ollama/local', $options->provider);
        self::assertSame('qwen:latest', $options->model);
        self::assertSame(['product-strategy'], $options->skills);
        self::assertSame(['product-strategy' => ['additional_context' => 'Prioritize recurring revenue.', 'audience' => 'investors']], $options->skillInputs);
        self::assertSame(['context.update-section'], $options->approvedTools);
        self::assertSame('Design the product.', $options->prompt);
    }

    public function testItRejectsUnknownOptions(): void
    {
        $this->expectExceptionMessage('Unknown ai:run option [--wat=yes].');
        AiRunOptions::parse(['code', '--wat=yes', 'Work.']);
    }
}
