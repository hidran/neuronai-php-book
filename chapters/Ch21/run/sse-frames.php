<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Workflow\Streaming\ProtocolEvent;
use NeuronAI\Workflow\Streaming\SSEEncoder;

/*
 * Section 21.1 - what the SSE endpoint actually puts on the wire.
 *
 *   php chapters/Ch21/run/sse-frames.php | cat -e
 *
 * No model needed: FakeAIProvider streams a canned answer in 8-character
 * chunks. Only TextChunk content is forwarded - tool calls and results stay
 * on the server (Section 7.4). SSEEncoder::frame() turns each ProtocolEvent
 * into one "data: {...}" line ending in the two newlines that close a frame;
 * `cat -e` marks each line end with "$".
 */

// An agent with no thread bound does not stream: the thread is its workflow ID.
$agent = Agent::make()->setThreadId('sse-demo');
$agent->setAiProvider(
    (new FakeAIProvider(new AssistantMessage('Your order ships tomorrow morning.')))->setStreamChunkSize(8)
);

$stream = $agent->stream(new UserMessage('Where is my order?'));

// No adapter or channel attached: stream() returns a Generator of native chunks.
\assert($stream instanceof Generator);

foreach ($stream as $chunk) {
    if (!$chunk instanceof TextChunk) {
        continue;
    }

    echo SSEEncoder::frame(new ProtocolEvent('text', ['content' => $chunk->content]));
}

echo "event: done\ndata: {}\n\n";

// The generator's return value is the final AgentState.
echo 'Final message: ' . ($stream->getReturn()->getMessage()?->getContent() ?? '') . "\n";
