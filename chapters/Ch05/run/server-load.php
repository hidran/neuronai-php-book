<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Chat\Messages\UserMessage;
use NeuronBook\Ch05\ToolDemoAgent;
use NeuronBook\Support\ProviderFactory;

/*
 * Section 5.2 - an inline tool.
 *
 *   php chapters/Ch05/run/server-load.php "How stressed is the server compared to fifteen minutes ago?"
 */

if (!ProviderFactory::isAvailable()) {
    exit("Provider not reachable. Start Ollama, or set a cloud key in .env.\n");
}

$question = $argv[1] ?? 'Is the server under stress right now?';

echo ToolDemoAgent::make()
    ->chat(new UserMessage($question))
    ->getMessage()
    ?->getContent() . PHP_EOL;
