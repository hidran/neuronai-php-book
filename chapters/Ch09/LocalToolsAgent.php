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
                /*
                 * escapeshellarg() on the command is not optional.
                 *
                 * StdioTransport::connect() builds its command line as
                 *
                 *     $commandLine = $command;
                 *     foreach ($args as $arg) {
                 *         $commandLine .= ' ' . escapeshellarg((string) $arg);
                 *     }
                 *
                 * The *arguments* are escaped; the *command* is not. Any
                 * interpreter path containing a space is therefore split by the
                 * shell and the process dies instantly, surfacing only as
                 * "MCP server process has terminated unexpectedly."
                 *
                 * That is the default on macOS with Laravel Herd, whose PHP
                 * lives under "~/Library/Application Support/...". Verified
                 * against neuron-ai 3.16.4.
                 */
                'command' => \escapeshellarg(PHP_BINARY),
                'args' => [__DIR__ . '/server/mcp-server.php'],
            ])->tools(),
        ];
    }
}
