<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Chat\Messages\UserMessage;
use NeuronBook\Ch12\DocsAgent;
use NeuronBook\Support\ProviderFactory;

/*
 * Section 12.2 - asking a question against the indexed documents.
 *
 *   php chapters/Ch12/run/ingest.php        # once
 *   php chapters/Ch12/run/ask.php "Why does changing the embeddings model matter?"
 */

if (!ProviderFactory::isAvailable()) {
    exit("Provider not reachable. Start Ollama, or set a cloud key in .env.\n");
}

$question = $argv[1] ?? 'Why does changing the embeddings model force a re-index?';

// chat() returns the final AgentState. getMessage() is null only when the
// run paused before any inference (a tool awaiting approval, for instance).
echo DocsAgent::make()
    ->setThreadId('docs-demo')
    ->chat(new UserMessage($question))
    ->getMessage()
    ?->getContent() . PHP_EOL;
