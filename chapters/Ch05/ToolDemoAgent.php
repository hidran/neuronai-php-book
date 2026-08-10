<?php

declare(strict_types=1);

namespace NeuronBook\Ch05;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use NeuronBook\Support\ProviderFactory;

/**
 * Section 5.2 - a tool defined inline with Tool::make().
 *
 * Inline is right for a one-off closure over local state. The moment the tool
 * needs its own dependencies or its own test, it becomes a class - which is
 * what WeatherTool in this same chapter shows.
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
            Tool::make(
                'get_server_load',
                'Returns the average CPU load of this server over a given time window. '
                . 'Use this whenever asked about current server load, stress, or performance.'
            )->addProperty(
                new ToolProperty(
                    name: 'window',
                    type: PropertyType::STRING,
                    description: 'The time window. Allowed values: "1m", "5m", "15m".',
                    required: true,
                )
            )->setCallable(function (string $window): string {
                $load = \sys_getloadavg();

                if ($load === false) {
                    return 'Load average is not available on this platform.';
                }

                $value = match ($window) {
                    '1m' => $load[0],
                    '5m' => $load[1],
                    '15m' => $load[2],
                    default => $load[0],
                };

                return \sprintf('Average load over %s: %.2f', $window, $value);
            }),
        ];
    }
}
