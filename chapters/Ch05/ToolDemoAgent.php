<?php

declare(strict_types=1);

namespace NeuronBook\Ch05;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolOutput;
use NeuronAI\Tools\ToolProperty;
use NeuronBook\Support\ProviderFactory;

/**
 * Section 5.2 - a tool declared inline, as an anonymous class.
 *
 * Inline is right for a one-off tool with no dependencies. The moment the tool
 * needs its own dependencies or its own test, it becomes a named class - which
 * is what WeatherTool in this same chapter shows.
 */
class ToolDemoAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return ProviderFactory::make();
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ['You are a system monitoring assistant.'],
            steps: ['Always read the real load values with your tools before answering.'],
            output: ['Answer in two sentences. Give the numbers you measured.'],
        );
    }

    /**
     * @return array<int, \NeuronAI\Tools\ToolInterface>
     */
    protected function tools(): array
    {
        return [
            new class extends Tool {
                protected string $name = 'get_server_load';

                protected ?string $description = 'Returns the average CPU load of this server over a given time window. '
                    . 'Use this whenever asked about current server load, stress, or performance.';

                protected function properties(): array
                {
                    return [
                        new ToolProperty(
                            name: 'window',
                            type: PropertyType::STRING,
                            description: 'The time window. Allowed values: "1m", "5m", "15m".',
                            required: true,
                        ),
                    ];
                }

                public function __invoke(string $window): string|ToolOutput
                {
                    $load = \sys_getloadavg();

                    if ($load === false) {
                        return ToolOutput::error('Load average is not available on this platform.');
                    }

                    $value = match ($window) {
                        '1m'  => $load[0],
                        '5m'  => $load[1],
                        '15m' => $load[2],
                        default => null,
                    };

                    if ($value === null) {
                        return ToolOutput::error("Invalid window \"{$window}\". Use \"1m\", \"5m\" or \"15m\".");
                    }

                    return \sprintf('Load average over %s: %.2f', $window, $value);
                }
            },
        ];
    }
}
