<?php

declare(strict_types=1);

namespace NeuronBook\Tests;

use NeuronAI\Chat\History\InMemoryChatHistory;
use NeuronBook\Ch03\AssistantAgent;
use PHPUnit\Framework\TestCase;

/**
 * Model-free checks on the book's own classes, for mistakes PHPStan cannot see
 * because they only happen when the objects meet at runtime.
 */
final class BookExamplesTest extends TestCase
{
    /**
     * Section 4.3. InMemoryChatHistory keys itself at random when it is given
     * no thread, so a chatHistory() hook that omits $this->threadId makes
     * make(threadId: ...) throw "Conflicting thread identity". The AG-UI
     * endpoint in Section 7.5 is exactly that call.
     */
    public function testAssistantAgentAcceptsAThreadId(): void
    {
        $agent = AssistantAgent::make(threadId: 'thread-1');

        self::assertSame('thread-1', $agent->getChatHistory()->getThreadId());
    }

    public function testAssistantAgentStillWorksWithoutAThreadId(): void
    {
        self::assertNotNull(AssistantAgent::make()->getChatHistory()->getThreadId());
    }

    /**
     * Pins the framework behaviour Section 4.3 describes. If this fails,
     * upstream stopped pre-binding the key: revisit that paragraph.
     */
    public function testInMemoryHistoryPreBindsARandomKey(): void
    {
        self::assertStringStartsWith('mem_', (string) (new InMemoryChatHistory())->getThreadId());
    }
}
