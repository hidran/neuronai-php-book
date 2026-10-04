<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Chat\Messages\UserMessage;
use NeuronBook\Ch09\LocalToolsAgent;
use NeuronBook\Support\ProviderFactory;

/*
 * Section 9.2 - an agent whose tools come from an MCP server over stdio.
 *
 *   php chapters/Ch09/run/mcp.php "What PHP version is the server running?"
 *
 * The server is chapters/Ch09/server/mcp-server.php in this same repository,
 * launched as a child process by McpConnector.
 */

if (!ProviderFactory::isAvailable()) {
    exit("Provider not reachable. Start Ollama, or set a cloud key in .env.\n");
}

$prompt = $argv[1] ?? 'What PHP version is the server running?';

echo LocalToolsAgent::make()
    ->setThreadId('mcp-demo')
    ->chat(new UserMessage($prompt))
    ->getMessage()
    ?->getContent() . PHP_EOL;
