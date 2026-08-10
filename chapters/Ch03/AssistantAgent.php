<?php

declare(strict_types=1);

namespace NeuronBook\Ch03;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\History\InMemoryChatHistory;
use NeuronAI\Providers\AIProviderInterface;
use NeuronBook\Support\ProviderFactory;

/**
 * The book's first agent - Chapter 3, "Your First Agent".
 *
 * Three methods carry the whole thing: provider() says which model,
 * instructions() says what the agent is, chatHistory() says how much of the
 * conversation it keeps. Everything in Parts II to V is an elaboration of
 * this shape.
 */
class AssistantAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return ProviderFactory::make();
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are a technical assistant specialised in PHP development.',
                'You are talking to experienced developers: no unrequested basics.',
            ],
            steps: [
                'Analyse the question and identify the real problem, not only the stated one.',
                'If the question is ambiguous, ask the single most useful clarifying question.',
            ],
            output: [
                'Answer in English.',
                'Use fenced code blocks with the language declared.',
                'No preambles such as "Certainly!" or "Great question".',
                'Maximum 200 words unless the code requires more.',
            ],
        );
    }

    protected function chatHistory(): ChatHistoryInterface
    {
        return new InMemoryChatHistory(
            contextWindow: ProviderFactory::contextWindow(),
        );
    }
}
