<?php

declare(strict_types=1);

namespace NeuronBook\Ch05;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Tools\Toolkits\Calculator\CalculatorToolkit;
use NeuronAI\Tools\Toolkits\Calculator\DivideTool;
use NeuronAI\Tools\Toolkits\Calculator\MeanTool;
use NeuronAI\Tools\Toolkits\Calculator\SumTool;
use NeuronAI\Tools\ToolInterface;
use NeuronBook\Support\ProviderFactory;
use Throwable;

/**
 * Section 5.7 - a custom tool and a filtered toolkit in one agent.
 *
 * only() narrows the calculator down to the three operations this agent can
 * justify having, which is the Section 5.4 argument: every extra tool is
 * schema you pay for on every request and a decision the model can get wrong.
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
     * @return array<int, ToolInterface|\NeuronAI\Tools\Toolkits\ToolkitInterface>
     */
    protected function tools(): array
    {
        return [
            WeatherTool::make()->setMaxRuns(4),

            CalculatorToolkit::make()->only([
                SumTool::class,
                DivideTool::class,
                MeanTool::class,
            ]),
        ];
    }

    protected function resolveToolErrorHandler(): ?callable
    {
        return function (Throwable $e, ToolInterface $tool): string {
            \error_log(\sprintf('[tool:%s] %s: %s', $tool->getName(), $e::class, $e->getMessage()));

            return "The {$tool->getName()} tool failed: {$e->getMessage()}. "
                 . 'Do not retry more than once. If it fails again, tell the user '
                 . 'the data is unavailable.';
        };
    }
}
