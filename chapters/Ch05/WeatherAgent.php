<?php

declare(strict_types=1);

namespace NeuronBook\Ch05;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Tools\ToolCall;
use NeuronAI\Tools\ToolOutput;
use NeuronAI\Tools\Toolkits\Calculator\CalculatorToolkit;
use NeuronAI\Tools\Toolkits\Calculator\EvaluateTool;
use NeuronAI\Tools\Toolkits\Calculator\MeanTool;
use NeuronBook\Support\ProviderFactory;
use Throwable;

/**
 * Lab 3 - a custom tool and a filtered toolkit in one agent.
 *
 * only() narrows the calculator down to the two tools this agent can justify
 * having, which is the Section 5.8 argument: every extra tool is schema you
 * pay for on every request and a decision the model can get wrong.
 */
class WeatherAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return ProviderFactory::make();
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are a weather assistant. You only report real data obtained from your tools.',
            ],
            steps: [
                'Derive the geographic coordinates of every location mentioned by the user.',
                'Call the weather tool once for each location.',
                'If a comparison or an average is requested, use the calculator tools.',
            ],
            output: [
                'Answer in English, in two sentences at most.',
                'Always state temperatures in degrees Celsius.',
            ],
        );
    }

    /**
     * @return array<int, \NeuronAI\Tools\ToolInterface|\NeuronAI\Tools\Toolkits\ToolkitInterface>
     */
    protected function tools(): array
    {
        return [
            WeatherTool::make()->setMaxRuns(4),

            CalculatorToolkit::make()->only([
                EvaluateTool::class,
                MeanTool::class,
            ]),
        ];
    }

    protected function resolveToolErrorHandler(): ?callable
    {
        return function (Throwable $e, ToolCall $call): ToolOutput {
            \error_log(\sprintf('[tool:%s] %s: %s', $call->getName(), $e::class, $e->getMessage()));

            return ToolOutput::error(
                "The {$call->getName()} tool failed. Do not retry more than once. "
                . 'If it fails again, tell the user the data is unavailable.'
            );
        };
    }
}
