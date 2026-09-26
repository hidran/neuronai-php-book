<?php

declare(strict_types=1);

namespace NeuronBook\Ch09;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\MCP\McpConnector;
use NeuronAI\MCP\McpTool;
use NeuronAI\Providers\AIProviderInterface;
use NeuronBook\Support\Env;
use NeuronBook\Support\ProviderFactory;

/**
 * Chapter 9 - consuming an MCP server.
 *
 * The spread operator matters: tools() returns a flat array, and
 * McpConnector::tools() returns a list, so it has to be unpacked.
 *
 * only() is the whole security argument of Section 9.4. A server you do not
 * control can add a tool tomorrow that you never reviewed; an allowlist means
 * a new tool appearing upstream does not silently become a new capability for
 * your agent.
 *
 * with() configures one discovered tool by its server-side name. Here it puts
 * the only tool that writes behind the approval gate: the run pauses before
 * update_contact executes and waits for a human decision (Chapter 15).
 */
class CrmAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return ProviderFactory::make();
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ['You answer questions about customers using the CRM tools available to you.'],
            steps: ['Look the contact up before answering. Never answer from memory.'],
            output: ['Give the contact details you retrieved, and nothing you did not.'],
        );
    }

    /**
     * @return array<int, \NeuronAI\Tools\ToolInterface>
     */
    protected function tools(): array
    {
        return [
            ...McpConnector::make([
                'url' => Env::get('CRM_MCP_URL', 'https://mcp.example.com') ?? '',
                'token' => Env::get('CRM_MCP_TOKEN', '') ?? '',
                'timeout' => 30,
            ])->only([
                'search_contacts',
                'get_contact',
                'update_contact',
            ])->with(
                'update_contact',
                fn (McpTool $tool) => $tool->requireApproval(),
            )->tools(),
        ];
    }
}
