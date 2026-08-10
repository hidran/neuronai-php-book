<?php

declare(strict_types=1);

namespace NeuronBook\Tests;

use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\History\EloquentChatHistory;
use NeuronAI\Chat\History\SQLChatHistory;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Evaluation\Assertions\Judges\FaithfulnessJudge;
use NeuronAI\Evaluation\Output\ConsoleOutput;
use NeuronAI\Evaluation\Output\JsonOutput;
use NeuronAI\Tools\ToolProperty;
use NeuronAI\Workflow\Events\Event;
use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Interrupt\WorkflowInterrupt;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Pins the API surface the book depends on.
 *
 * Every assertion here corresponds to a claim printed in the book. If the
 * framework renames something, this suite says which page went stale - which
 * is the whole point of a companion repository that runs in CI.
 */
final class ApiContractTest extends TestCase
{
    /**
     * @return array<string, array{class-string}>
     */
    public static function bookClassProvider(): array
    {
        return [
            'StartEvent lives under Workflow\Events' => [StartEvent::class],
            'StopEvent lives under Workflow\Events' => [StopEvent::class],
            'Event lives under Workflow\Events' => [Event::class],
            'WorkflowInterrupt lives under Workflow\Interrupt' => [WorkflowInterrupt::class],
            'FaithfulnessJudge lives under Assertions\Judges' => [FaithfulnessJudge::class],
            'ConsoleOutput, not ConsoleDriver' => [ConsoleOutput::class],
            'JsonOutput, not JsonDriver' => [JsonOutput::class],
            'SourceType lives under Chat\Enums' => [SourceType::class],
        ];
    }

    /**
     * @param class-string $fqcn
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('bookClassProvider')]
    public function testClassExistsWhereTheBookSaysItDoes(string $fqcn): void
    {
        self::assertTrue(
            class_exists($fqcn) || interface_exists($fqcn) || enum_exists($fqcn),
            "{$fqcn} no longer exists.",
        );
    }

    public function testContentBlocksTakeContentNotSource(): void
    {
        $names = self::parameterNames(FileContent::class);

        self::assertContains('content', $names, 'FileContent should accept $content.');
        self::assertNotContains('source', $names, 'FileContent must not accept $source.');
    }

    public function testToolPropertyHasNoNullableParameter(): void
    {
        $names = self::parameterNames(ToolProperty::class);

        self::assertSame(['name', 'type', 'description', 'required', 'enum'], $names);
    }

    /**
     * The library really is inconsistent here, and the book trips on it.
     */
    public function testChatHistoryThreadParameterNamingDiffers(): void
    {
        self::assertContains('threadId', self::parameterNames(EloquentChatHistory::class));
        self::assertContains('thread_id', self::parameterNames(SQLChatHistory::class));
    }

    /**
     * StopEvent carries an optional result - Chapter 16 relies on it.
     */
    public function testStopEventAcceptsAResult(): void
    {
        self::assertContains('result', self::parameterNames(StopEvent::class));
        self::assertNull((new StopEvent())->getResult());
        self::assertSame('done', (new StopEvent('done'))->getResult());
    }

    /**
     * Node::checkpoint() and Node::interrupt() are protected, which is why the
     * book always calls them as $this->checkpoint(...) from inside a node.
     */
    public function testNodeExposesCheckpointAndInterruptToSubclasses(): void
    {
        $rc = new ReflectionClass(\NeuronAI\Workflow\Node::class);

        foreach (['checkpoint', 'interrupt', 'interruptIf', 'consumeResumeRequest'] as $method) {
            self::assertTrue($rc->hasMethod($method), "Node::{$method}() is gone.");
            self::assertTrue($rc->getMethod($method)->isProtected(), "Node::{$method}() changed visibility.");
        }
    }

    /**
     * Agent::structured() must keep $class optional, or every example that
     * relies on getOutputClass() breaks.
     */
    public function testStructuredAllowsOmittingTheOutputClass(): void
    {
        $method = (new ReflectionClass(\NeuronAI\Agent\Agent::class))->getMethod('structured');
        $params = $method->getParameters();

        self::assertSame('messages', $params[0]->getName());
        self::assertSame('class', $params[1]->getName());
        self::assertTrue($params[1]->allowsNull(), 'structured($class) must stay optional.');
        self::assertSame('maxRetries', $params[2]->getName());
    }

    /**
     * The book's whole tool chapter assumes these fluent setters return $this.
     */
    public function testToolFluentSettersExist(): void
    {
        $rc = new ReflectionClass(\NeuronAI\Tools\Tool::class);

        foreach (['addProperty', 'setCallable', 'setMaxRuns', 'visible'] as $method) {
            self::assertTrue($rc->hasMethod($method), "Tool::{$method}() is gone.");
        }
    }

    /**
     * Guards the documented Ollama constructor order used by ProviderFactory.
     */
    public function testOllamaProviderSignature(): void
    {
        $names = self::parameterNames(\NeuronAI\Providers\Ollama\Ollama::class);

        self::assertSame(['url', 'model', 'parameters', 'httpClient'], $names);
    }

    public function testEmbeddingsProviderIsNamedWithTheTrailingS(): void
    {
        self::assertTrue(
            class_exists(\NeuronAI\RAG\Embeddings\OpenAIEmbeddingsProvider::class),
            'It is OpenAIEmbeddingsProvider - not OpenAIEmbeddings, not OpenAIEmbeddingProvider.',
        );
    }

    /**
     * @param class-string $fqcn
     * @return list<string>
     */
    private static function parameterNames(string $fqcn): array
    {
        $ctor = (new ReflectionClass($fqcn))->getConstructor();

        if ($ctor === null) {
            return [];
        }

        return array_map(
            static fn (\ReflectionParameter $p): string => $p->getName(),
            $ctor->getParameters(),
        );
    }
}
