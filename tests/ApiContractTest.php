<?php

declare(strict_types=1);

namespace NeuronBook\Tests;

use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\History\EloquentMessageStore;
use NeuronAI\Chat\History\FileMessageStore;
use NeuronAI\Chat\History\InMemoryMessageStore;
use NeuronAI\Chat\History\SQLMessageStore;
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
            // v4 locations the book prints (chapters 5, 7, 10, 12, 15, 21-23)
            'ApprovalRequest moved to Agent\Interrupt' => [\NeuronAI\Agent\Interrupt\ApprovalRequest::class],
            'ToolRunsExceededException is the only run-limit exception' => [\NeuronAI\Exceptions\ToolRunsExceededException::class],
            'RunInFlightException' => [\NeuronAI\Exceptions\RunInFlightException::class],
            'StaleWorkflowRunException' => [\NeuronAI\Exceptions\StaleWorkflowRunException::class],
            'ToolOutput' => [\NeuronAI\Tools\ToolOutput::class],
            'TrackByInputs' => [\NeuronAI\Tools\TrackByInputs::class],
            'EvaluateTool replaces the per-operation calculator tools' => [\NeuronAI\Tools\Toolkits\Calculator\EvaluateTool::class],
            'Stream adapters live under Agent\Adapters' => [\NeuronAI\Agent\Adapters\AGUIAdapter::class],
            'VercelAIAdapter' => [\NeuronAI\Agent\Adapters\VercelAIAdapter::class],
            'SSE framing happens at the edge' => [\NeuronAI\Workflow\Streaming\SSEEncoder::class],
            'Streaming channels' => [\NeuronAI\Workflow\Streaming\Channel\PusherChannel::class],
            'CurlHttpClient is the default HTTP client' => [\NeuronAI\HttpClient\Curl\CurlHttpClient::class],
            'DocumentSchema' => [\NeuronAI\RAG\Schema\DocumentSchema::class],
            'SearchRequest' => [\NeuronAI\RAG\VectorStore\SearchRequest::class],
            'Filter' => [\NeuronAI\RAG\VectorStore\Filter\Filter::class],
            'FakeAIProvider' => [\NeuronAI\Testing\FakeAIProvider::class],
            'LogListener' => [\NeuronAI\Observability\LogListener::class],
            'EloquentPersistence' => [\NeuronAI\Workflow\Persistence\EloquentPersistence::class],
            'Laravel WorkflowStore model' => [\NeuronAI\Laravel\Models\WorkflowStore::class],
        ];
    }

    /**
     * @param class-string $fqcn
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('bookClassProvider')]
    public function testClassExistsWhereTheBookSaysItDoes(string $fqcn): void
    {
        self::assertTrue(
            class_exists($fqcn) || interface_exists($fqcn) || enum_exists($fqcn) || trait_exists($fqcn),
            "{$fqcn} no longer exists.",
        );
    }

    public function testContentBlocksTakeContentNotSource(): void
    {
        $names = self::parameterNames(FileContent::class);

        self::assertContains('content', $names, 'FileContent should accept $content.');
        self::assertNotContains('source', $names, 'FileContent must not accept $source.');
    }

    public function testToolPropertyAcceptsNullable(): void
    {
        $names = self::parameterNames(ToolProperty::class);

        self::assertSame(['name', 'type', 'description', 'required', 'enum', 'nullable'], $names);
    }

    /**
     * v4 has no *ChatHistory classes and no thread ID on the store: the thread
     * is bound on the agent, and a message store only says where messages live.
     */
    public function testMessageStoreConstructors(): void
    {
        self::assertSame([], self::parameterNames(InMemoryMessageStore::class));
        self::assertSame(['directory', 'prefix', 'ext'], self::parameterNames(FileMessageStore::class));
        self::assertSame(['modelClass'], self::parameterNames(EloquentMessageStore::class));
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

        foreach (['checkpoint', 'memoize', 'interrupt', 'interruptIf', 'awaitEvent', 'sleepUntil'] as $method) {
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
     * v4 made Tool abstract: every tool in the book is a subclass.
     */
    public function testToolIsAbstractAndKeepsItsFluentSetters(): void
    {
        $rc = new ReflectionClass(\NeuronAI\Tools\Tool::class);

        self::assertTrue($rc->isAbstract(), 'Tool is no longer abstract.');

        foreach (['addProperty', 'setMaxRuns', 'visible', 'requireApproval', 'suppressApproval', 'withApprovalPolicy'] as $method) {
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
     * Chapter 5.5: binding is casting. The v3 advice to widen float parameters
     * to float|int|string is obsolete because this conversion happens first.
     */
    public function testNumberPropertyCastsNumericStrings(): void
    {
        $property = new ToolProperty('x', \NeuronAI\Tools\PropertyType::NUMBER);

        self::assertSame(45.07, $property->cast('45.07'));
        self::assertSame(5, $property->cast('5'));
    }

    /**
     * Chapter 5.10 and Appendix A item 45: approvalPolicy() takes no arguments.
     */
    public function testApprovalPolicyTakesNoParameters(): void
    {
        $method = (new ReflectionClass(\NeuronAI\Tools\Tool::class))->getMethod('approvalPolicy');

        self::assertTrue($method->isProtected());
        self::assertSame(0, $method->getNumberOfParameters());
    }

    /**
     * Chapters 5, 15 and 22 build on these agent verbs.
     */
    public function testAgentApprovalAndIdentityVerbsExist(): void
    {
        $rc = new ReflectionClass(\NeuronAI\Agent\Agent::class);

        foreach (['pendingApprovals', 'submitApprovalDecisions', 'resetConversation', 'abandon', 'setThreadId', 'getThreadId'] as $method) {
            self::assertTrue($rc->hasMethod($method), "Agent::{$method}() is gone.");
        }
    }

    /**
     * Chapter 4.3: the SQL store is (pdo, table) - the thread is the agent's.
     */
    public function testSqlMessageStoreArgumentOrder(): void
    {
        self::assertSame(['pdo', 'table'], self::parameterNames(SQLMessageStore::class));
    }

    /**
     * Chapters 13 and 15: no persistence or resume token in the constructor any more.
     */
    public function testWorkflowConstructorAndResumeFences(): void
    {
        self::assertSame(['workflowId', 'state'], self::parameterNames(\NeuronAI\Workflow\Workflow::class));

        // v4 has no Workflow::resume(): continuing is run(ExecutionRequest::resume(...)) or submitInputs()->run().
        self::assertFalse((new ReflectionClass(\NeuronAI\Workflow\Workflow::class))->hasMethod('resume'));

        $resume = (new ReflectionClass(\NeuronAI\Workflow\Executor\ExecutionRequest::class))->getMethod('resume');
        $names = array_map(static fn (\ReflectionParameter $p): string => $p->getName(), $resume->getParameters());

        self::assertContains('expectedRunId', $names);
        self::assertContains('expectedExecutionAttempt', $names);
    }

    /**
     * Chapter 12.5 and the glossary: five methods, search/delete take value objects.
     */
    public function testVectorStoreInterfaceShape(): void
    {
        $methods = array_map(
            static fn (\ReflectionMethod $m): string => $m->getName(),
            (new ReflectionClass(\NeuronAI\RAG\VectorStore\VectorStoreInterface::class))->getMethods(),
        );
        sort($methods);

        self::assertSame(['addDocument', 'addDocuments', 'delete', 'getSchema', 'search'], $methods);
    }

    /**
     * Section 22.3 documents that the stale-attempt fence
     * throws a plain WorkflowException. When this starts failing, upstream has
     * aligned the two fences: update the callout in 22.3.
     */
    public function testStaleAttemptFenceStillThrowsPlainWorkflowException(): void
    {
        $source = (string) file_get_contents(
            (string) (new ReflectionClass(\NeuronAI\Workflow\WorkflowEngine::class))->getFileName(),
        );

        self::assertStringContainsString("Stale continuation for workflow ID", $source);
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
