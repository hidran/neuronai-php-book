<?php

declare(strict_types=1);

namespace NeuronBook\Tests;

use InvalidArgumentException;
use NeuronAI\Chat\History\InMemoryChatHistory;
use NeuronBook\Ch03\AssistantAgent;
use NeuronBook\Ch14\ContentWorkflowState;
use NeuronBook\Ch22\RefundApprovalRequest;
use NeuronBook\Support\ProviderFactory;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

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

    /**
     * Guards a PHP 8.5.4 engine bug, not a style rule.
     *
     * Inside a namespace, piping into a first-class callable of an internal
     * function written WITHOUT its leading backslash - the pipe operator
     * followed by an unqualified trim(...) - corrupts the heap and the
     * process dies with "zend_mm_heap corrupted". The unqualified name goes
     * through the namespace fallback lookup, and that path is what breaks.
     * Writing \trim(...) resolves the function at compile time and is safe;
     * so are closures, arrow functions and Class::method(...) in a pipe.
     *
     * Every example in the book already qualifies global functions, so this
     * only has to catch the one that slips through.
     */
    public function testPipesNeverTargetAnUnqualifiedFunction(): void
    {
        $root = \dirname(__DIR__);
        $offenders = [];

        foreach (['chapters', 'examples', 'tests'] as $dir) {
            if (!\is_dir("{$root}/{$dir}")) {
                continue;
            }

            /** @var iterable<SplFileInfo> $files */
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$dir}", RecursiveDirectoryIterator::SKIP_DOTS));

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php' || \preg_match('#/(vendor|node_modules|storage)/#', $file->getPathname()) === 1) {
                    continue;
                }

                $lines = \file($file->getPathname()) ?: [];
                $source = \implode('', $lines);

                // A pipe, optional whitespace (newlines included), then a bare
                // identifier and (...) - a backslash cannot match [a-z_].
                if (\preg_match_all('/\|>\s*[a-z_][a-z0-9_]*\(\.\.\.\)/i', $source, $matches, \PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                foreach ($matches[0] as [$match, $offset]) {
                    $line = \substr_count($source, "\n", 0, $offset) + 1;
                    $offenders[] = \substr($file->getPathname(), \strlen($root) + 1) . ":{$line}  " . \preg_replace('/\s+/', ' ', $match);
                }
            }
        }

        self::assertSame(
            [],
            $offenders,
            "Qualify internal functions in pipes (\\trim(...), not trim(...)) - PHP 8.5.4 corrupts the heap otherwise:\n"
            . \implode("\n", $offenders),
        );
    }

    /**
     * Section 22.4. The version-2 field is set with a clone-with wither, which
     * must leave the original request untouched and keep everything else.
     */
    public function testRefundRequestWithCurrencyReturnsAModifiedCopy(): void
    {
        $original = new RefundApprovalRequest('Refund order 7?', 7, 120.0);
        $copy = $original->withCurrency('GBP');

        self::assertNotSame($original, $copy);
        self::assertSame('EUR', $original->jsonSerialize()['currency']);
        self::assertSame('GBP', $copy->jsonSerialize()['currency']);
        self::assertSame(
            \array_diff_key($original->jsonSerialize(), ['currency' => true]),
            \array_diff_key($copy->jsonSerialize(), ['currency' => true]),
        );
    }

    /**
     * Section 14.3. lastFeedback() is null until a revision exists, then the
     * most recent one wins.
     */
    public function testContentStateLastFeedback(): void
    {
        $state = new ContentWorkflowState();

        self::assertNull($state->lastFeedback());

        $state->addRevision('v1', 'Too long.')->addRevision('v2', 'Tighten the opening.');

        self::assertSame('Tighten the opening.', $state->lastFeedback());
        self::assertFalse($state->hasReachedLimit());
    }

    /**
     * Section 3.6. OLLAMA_URL is parsed with the URI extension: a malformed
     * value makes make() fail loudly and isAvailable() answer false.
     */
    public function testMalformedOllamaUrlIsRejected(): void
    {
        $previous = $_ENV['OLLAMA_URL'] ?? null;
        $_ENV['OLLAMA_URL'] = 'localhost:11434/api';

        try {
            self::assertFalse(ProviderFactory::isAvailable('ollama'));

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('OLLAMA_URL');
            ProviderFactory::make('ollama');
        } finally {
            if ($previous === null) {
                unset($_ENV['OLLAMA_URL']);
            } else {
                $_ENV['OLLAMA_URL'] = $previous;
            }
        }
    }
}
