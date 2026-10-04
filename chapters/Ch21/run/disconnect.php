<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Agent\Agent;
use NeuronAI\Chat\History\InMemoryMessageStore;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ChatHistoryException;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolCall;
use NeuronAI\Workflow\Executor\ExecutionRequest;
use NeuronAI\Workflow\Persistence\InMemoryPersistence;
use NeuronAI\Workflow\WorkflowStatus;

/*
 * Section 21.6 - what a disconnected client does to the next message.
 *
 *   php chapters/Ch21/run/disconnect.php
 *
 * No model needed. Two agent instances share one persistence object and one
 * message store, as two PHP requests share a database. The first streams and
 * "loses" its client; the second is the user's next message on the same thread.
 *
 * A consumer that stops pulling before the run has settled fails the run at
 * once, so the thread is never locked. What matters is what the failed turn
 * had already stored:
 *
 *   1. the client left before anything was stored - the next message works;
 *   2. it left after a tool step - the question is already in the history,
 *      and the next message is refused with ChatHistoryException;
 *   3. the same again, but the failed turn is finished first - the next
 *      message works, and the tool has run exactly once.
 */

$scenario = static function (string $label, bool $toolStep, bool $recover): void {
    $persistence = new InMemoryPersistence();
    $store = new InMemoryMessageStore();
    $toolRuns = new ArrayObject();

    $lookup = new class ($toolRuns) extends Tool {
        protected string $name = 'lookup_order';

        protected ?string $description = 'Look up the status of an order.';

        /** @param ArrayObject<int, string> $runs */
        public function __construct(private readonly ArrayObject $runs)
        {
        }

        public function __invoke(): string
        {
            $this->runs->append('ran');

            return 'Order shipped.';
        }
    };

    $responses = $toolStep
        ? [new ToolCallMessage(null, [new ToolCall('lookup_order', 'call_1')])]
        : [];
    $responses[] = new AssistantMessage('A long answer nobody will read.');

    if ($recover) {
        // The recovered turn asks the model again for the answer it never delivered.
        $responses[] = new AssistantMessage('The answer to the first question.');
    }

    $responses[] = new AssistantMessage('Here is the answer to your new question.');

    $provider = (new FakeAIProvider(...$responses))->setStreamChunkSize(6);

    $agent = static fn (): Agent => Agent::make()
        ->setThreadId('conv:42')
        ->setPersistence($persistence)
        ->setMessageStore($store)
        ->setAiProvider($provider)
        ->addTool($lookup);

    $stream = $agent()->stream(new UserMessage('First question'));

    foreach ($stream as $chunk) {
        // Stop at the first text chunk - after the tool step, if there is one.
        // connection_aborted() would be true here in a real request.
        if ($chunk instanceof TextChunk) {
            break;
        }
    }

    // Releasing the generator is what PHP does when the request ends: the run
    // is recorded as failed, not left "running" under the lease.
    unset($stream, $chunk);

    $next = $agent();

    if ($recover) {
        // Section 18.4's recoverFailedTurn(): finish the failed turn first.
        $run = $next->inspect();

        if ($run?->status === WorkflowStatus::Failed) {
            $next->run(ExecutionRequest::resume(
                expectedRunId: $run->runId,
                expectedExecutionAttempt: $run->executionAttempt,
            ));
        }
    }

    try {
        $state = $next->chat(new UserMessage('Second question'));

        echo "{$label}: next message answered - " . ($state->getMessage()?->getContent() ?? '')
            . ' (tool ran ' . \count($toolRuns) . "x)\n";
    } catch (ChatHistoryException $e) {
        echo "{$label}: next message refused - ChatHistoryException: {$e->getMessage()}\n";
    }
};

$scenario('1. left before a tool step ', false, false);
$scenario('2. left after a tool step  ', true, false);
$scenario('3. same, failed turn done  ', true, true);
