<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolCallChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolResultChunk;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronBook\Ch03\AssistantAgent;
use NeuronBook\Ch07\ServerConfigurationTool;
use NeuronBook\Support\ProviderFactory;

/*
 * Section 7.4 - tools inside the stream.
 *
 * The debugging view: every tool call and result is printed raw. Fine in a
 * terminal, a leak in front of a customer - see the label allowlist in 7.4.
 *
 *   php chapters/Ch07/run/stream-tools.php
 */

if (!ProviderFactory::isAvailable()) {
    exit("Provider not reachable. Start Ollama, or set a cloud key in .env.\n");
}

$stream = AssistantAgent::make()
    ->setThreadId('stream-tools-demo')
    ->addTool(new ServerConfigurationTool())
    ->stream(
        new UserMessage("What's the IP address of the server?")
    );

\assert($stream instanceof Generator);

foreach ($stream as $chunk) {
    if ($chunk instanceof ToolCallChunk) {
        echo "\n- Calling tool: " . $chunk->tool->getName();
        echo "\n- Input: " . \json_encode($chunk->tool->getInputs()) . "\n";
        continue;
    }

    if ($chunk instanceof ToolResultChunk) {
        echo "- Tool " . $chunk->tool->getName() . " completed";
        echo "\n- Result: " . $chunk->tool->getResult() . "\n";
        continue;
    }

    if ($chunk instanceof TextChunk) {
        echo $chunk->content;
    }
}

echo "\n";
