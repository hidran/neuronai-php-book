<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Chat\Messages\UserMessage;
use NeuronBook\Ch03\AssistantAgent;
use NeuronBook\Support\ProviderFactory;

/*
 * Section 7.2 - stream() and events().
 *
 * The v3 shape that Chapter 7 insists on: stream() returns a handler, and you
 * iterate handler->events(). The chunk is an object; the text lives in
 * $chunk->content, not in the chunk itself.
 *
 *   php chapters/Ch07/run/stream.php "Explain the Repository pattern"
 */

if (!ProviderFactory::isAvailable()) {
    exit("Provider not reachable. Start Ollama, or set a cloud key in .env.\n");
}

$prompt = $argv[1] ?? 'Explain the Repository pattern and when using it is a mistake.';

$start = \microtime(true);
$first = null;

$handler = AssistantAgent::make()->stream(new UserMessage($prompt));

foreach ($handler->events() as $chunk) {
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
