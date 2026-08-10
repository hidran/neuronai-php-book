<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Chat\Messages\UserMessage;
use NeuronBook\Ch03\AssistantAgent;
use NeuronBook\Support\ProviderFactory;

/*
 * Section 3.3 - the four-line script that proves the agent works.
 *
 *   php chapters/Ch03/run/chat.php "your question here"
 */

if (!ProviderFactory::isAvailable()) {
    exit("Provider not reachable. Start Ollama, or set a cloud key in .env.\n");
}

$prompt = $argv[1] ?? 'Explain the difference between readonly and final in PHP 8, in three lines.';

$response = AssistantAgent::make()
    ->chat(new UserMessage($prompt))
    ->getMessage();

echo $response->getContent() . PHP_EOL;
