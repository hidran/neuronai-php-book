<?php

declare(strict_types=1);

namespace NeuronBook\Ch06;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronBook\Support\ProviderFactory;

/**
 * Chapter 6 - structured output.
 *
 * Nothing here mentions the output class: structured() takes it per call.
 * An agent that always returns the same shape can pin it with
 * getOutputClass() instead (Section 6.3).
 */
class ExtractorAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return ProviderFactory::make();
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ['You extract structured records from free-form text.'],
            steps: ['Read the text and fill every required field you can justify from it.'],
            output: ['Return only the requested structure. Never invent values.'],
        );
    }
}
