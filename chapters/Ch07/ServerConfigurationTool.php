<?php

declare(strict_types=1);

namespace NeuronBook\Ch07;

use NeuronAI\Tools\Tool;

/**
 * Section 7.4 - a tool to stream around.
 *
 * It takes no input and answers instantly with a fixed configuration, so the
 * example needs no network: the point is the ToolCallChunk / ToolResultChunk
 * pair it produces in the middle of the stream, not what it returns.
 */
class ServerConfigurationTool extends Tool
{
    protected string $name = 'get_server_configuration';

    protected ?string $description = 'Retrieve the server network configuration: hostname, IP address and gateway. '
        . 'Use this whenever the user asks about the server address or network settings.';

    public function __invoke(): string
    {
        return (string) \json_encode([
            'hostname' => 'app-01',
            'ip' => '192.168.0.10',
            'gateway' => '192.168.0.1',
        ]);
    }
}
