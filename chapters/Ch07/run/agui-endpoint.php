<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Agent\Adapters\AGUIAdapter;
use NeuronAI\Agent\AgentState;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Streaming\ProtocolEvent;
use NeuronAI\Workflow\Streaming\SSEEncoder;
use NeuronBook\Ch03\AssistantAgent;

/*
 * Section 7.5 - a minimal AG-UI endpoint, new turns only.
 *
 * Serve it with PHP's built-in server and POST a RunAgentInput payload:
 *
 *   php -S localhost:8000 chapters/Ch07/run/agui-endpoint.php
 *   curl -N localhost:8000 -d '{"threadId":"t1","runId":"r1","messages":[{"id":"m1","role":"user","content":"Hi"}]}'
 *
 * Continuations (approvals, frontend tool results) go through
 * submitInputs() with AGUIInputTranslator - see Chapters 21 and 22.
 */

/** @var array{threadId: string, runId?: string, messages: list<array<string, mixed>>, state?: array<string, mixed>} $input */
$input = \json_decode((string) \file_get_contents('php://input'), true, flags: \JSON_THROW_ON_ERROR);

$messages = $input['messages'];

// PHP 8.5: array_last() returns null for an empty list - no key juggling.
$last = \array_last($messages);

if (($last['role'] ?? null) !== 'user') {
    \http_response_code(400);
    exit('A new turn must end with a user message.');
}

// setStreamAdapter() takes a factory: an adapter holds the state of one stream,
// and the agent builds a fresh one for each execution segment.
$makeAdapter = static fn (): AGUIAdapter => new AGUIAdapter(
    threadId: $input['threadId'],
    runId: $input['runId'] ?? null,
    messages: $messages,
    state: $input['state'] ?? [],
);

foreach ($makeAdapter()->getHeaders() as $name => $value) {
    \header("{$name}: {$value}");
}

// With an adapter attached the generator yields ProtocolEvent objects;
// stream()'s declared type only promises "object", so say it here.
/** @var Generator<int, ProtocolEvent, mixed, AgentState> $stream */
$stream = AssistantAgent::make(workflowId: $input['threadId'])
    ->setStreamAdapter($makeAdapter)
    ->stream(new UserMessage((string) $last['content']));

foreach (SSEEncoder::encode($stream) as $line) {
    echo $line;
    \flush();
}
