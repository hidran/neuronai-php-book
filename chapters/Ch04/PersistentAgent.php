<?php

declare(strict_types=1);

namespace NeuronBook\Ch04;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\Providers\AIProviderInterface;
use NeuronBook\Support\ProviderFactory;

/**
 * Lab 2 - an agent whose conversation survives a process restart.
 *
 * The history is built without a thread ID. Identity enters once, through
 * PersistentAgent::make(threadId: ...), and the agent binds it into the
 * history before first use.
 */
class PersistentAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return ProviderFactory::make();
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ['You are a technical assistant that remembers conversation context.'],
            output: ['Answer concisely.'],
        );
    }

    protected function chatHistory(): ChatHistoryInterface
    {
        return new FileChatHistory(
            directory: \dirname(__DIR__, 2) . '/storage/chat',
            contextWindow: ProviderFactory::contextWindow(),
        );
    }
}
