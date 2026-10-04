<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronBook\Ch03\AssistantAgent;
use NeuronBook\Support\ProviderFactory;

/*
 * Section 7.2 - stream() is the generator.
 *
 * You iterate what stream() returns directly. It yields objects, not strings:
 * keep the TextChunk instances and read $chunk->content. When the loop ends,
 * the generator's return value is the final AgentState.
 *
 *   php chapters/Ch07/run/stream.php "Explain the Repository pattern"
 */

if (!ProviderFactory::isAvailable()) {
    exit("Provider not reachable. Start Ollama, or set a cloud key in .env.\n");
}

$prompt = $argv[1] ?? 'Explain the Repository pattern and when using it is a mistake.';

$start = \microtime(true);
$first = null;

$stream = AssistantAgent::make()->setThreadId('stream-demo')->stream(new UserMessage($prompt));

// No adapter and no channel attached, so stream() returned a Generator.
\assert($stream instanceof Generator);

foreach ($stream as $chunk) {
    if (!$chunk instanceof TextChunk) {
        continue;
    }

    $first ??= \microtime(true);

    echo $chunk->content;
    \flush();
}

$end = \microtime(true);

\printf(
    "\n\n[first token: %.2fs | total: %.2fs]\n",
    ($first ?? $end) - $start,
    $end - $start,
);

// The complete assistant message, assembled for you.
$message = $stream->getReturn()->getMessage();

\printf("[assembled: %d characters]\n", \strlen($message?->getContent() ?? ''));
