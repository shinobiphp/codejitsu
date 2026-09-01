<?php
declare(strict_types=1);
namespace Codejitsu\Ai\Tests\Vessels;

use Codejitsu\Ai\Runtime\AiRequest;
use Codejitsu\Ai\Runtime\AiResponse;
use Codejitsu\Ai\Runtime\AiRuntime;
use Codejitsu\Ai\Vessels\VesselSession;
use PHPUnit\Framework\TestCase;

final class VesselSessionTest extends TestCase
{
    public function testItMaintainsAndClearsSuccessfulConversationHistory(): void
    {
        $runtime = new RecordingRuntime();
        $session = new VesselSession($runtime, 'Be precise.', null, [], [], []);
        self::assertSame('reply: one', $session->send('one')->text);
        self::assertSame('reply: two', $session->send('two')->text);
        self::assertCount(3, $runtime->requests[1]->messages);
        $session->clear();
        $session->send('fresh');
        self::assertCount(1, $runtime->requests[2]->messages);
    }

    public function testFailedRequestsDoNotAppendAssistantHistory(): void
    {
        $runtime = new RecordingRuntime(failFirst: true);
        $session = new VesselSession($runtime, 'Act.', null, [], [], []);
        try { $session->send('bad'); } catch (\RuntimeException) {}
        $session->send('retry');
        self::assertCount(2, $runtime->requests[1]->messages);
        self::assertSame(['bad', 'retry'], array_map(fn ($message) => $message->content, $runtime->requests[1]->messages));
    }
}

final class RecordingRuntime implements AiRuntime
{
    public array $requests = [];
    public function __construct(private bool $failFirst = false) {}
    public function run(AiRequest $request): AiResponse
    {
        $this->requests[] = $request;
        if ($this->failFirst) { $this->failFirst = false; throw new \RuntimeException('failed'); }
        $last = $request->messages[array_key_last($request->messages)];
        return new AiResponse('reply: ' . $last->content);
    }
}
