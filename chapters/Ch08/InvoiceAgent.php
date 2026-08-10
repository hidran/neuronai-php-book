<?php

declare(strict_types=1);

namespace NeuronBook\Ch08;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronBook\Support\ProviderFactory;

/**
 * Section 8.4 - multimodal input plus structured output.
 *
 * getOutputClass() pins the shape, so run/extract.php can call structured()
 * without repeating the class on every call.
 */
class InvoiceAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return ProviderFactory::make();
    }

    protected function getOutputClass(): string
    {
        return Invoice::class;
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You extract structured data from invoice documents.',
                'You are not an accountant. You transcribe what is on the document; you do not judge it.',
            ],
            steps: [
                'Read every line item, including any that continue onto a second page.',
                'Transcribe values exactly. Do not correct apparent errors on the document.',
                'Convert dates to ISO 8601 regardless of the format printed.',
            ],
            output: [
                'Return only the structured record.',
                'Never invent a value that is not on the document.',
            ],
        );
    }
}
