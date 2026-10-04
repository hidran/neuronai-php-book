<?php

declare(strict_types=1);

namespace NeuronBook\Ch04;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\History\FileMessageStore;
use NeuronAI\Chat\History\MessageStoreInterface;
use NeuronAI\Providers\AIProviderInterface;
use NeuronBook\Support\ProviderFactory;

/**
 * Lab 2 - an agent whose conversation survives a process restart.
 *
 * The store is built without a thread ID. Identity enters once, through
 * PersistentAgent::make(workflowId: ...) (or setThreadId()), and the agent
 * opens the store's history for that thread.
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

    protected function messageStore(): MessageStoreInterface
    {
        return new FileMessageStore(
            directory: \dirname(__DIR__, 2) . '/storage/chat',
        );
    }

    protected function contextWindow(): int
    {
        return ProviderFactory::contextWindow();
    }
}
