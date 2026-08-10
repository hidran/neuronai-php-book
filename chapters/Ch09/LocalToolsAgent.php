<?php

declare(strict_types=1);

namespace NeuronBook\Ch09;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\MCP\McpConnector;
use NeuronAI\Providers\AIProviderInterface;
use NeuronBook\Support\ProviderFactory;

/**
 * Section 9.2 - the stdio transport.
 *
 * 'command' plus 'args' launches a local process and speaks JSON-RPC over its
 * stdin/stdout. No network, no token. This repository ships a tiny server at
 * chapters/Ch09/server/mcp-server.php so the example actually runs.
 */
class LocalToolsAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return ProviderFactory::make();
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ['You answer questions about this machine using your tools.'],
            steps: ['Always call a tool rather than guessing.'],
            output: ['One short sentence containing the value you retrieved.'],
        );
    }

    /**
     * @return array<int, \NeuronAI\Tools\ToolInterface>
     */
    protected function tools(): array
    {
        return [
            ...McpConnector::make([
                'command' => PHP_BINARY,
                'args' => [__DIR__ . '/server/mcp-server.php'],
            ])->tools(),
        ];
    }
}
