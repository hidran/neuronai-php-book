<?php

declare(strict_types=1);

namespace NeuronBook\Ch12;

use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use NeuronBook\Support\ProviderFactory;

/**
 * Chapter 12 - a RAG agent over local Markdown.
 *
 * Three extra methods on top of a normal Agent: embeddings(), vectorStore()
 * and everything else stays the same. That is Chapter 11's argument made
 * concrete - RAG *is* a Workflow, and an Agent is one too, so the API you
 * already know does not change shape.
 *
 * Everything here runs locally: nomic-embed-text through Ollama for the
 * vectors, and a flat file for the store. No account, no API key, no service.
 */
class DocsAgent extends RAG
{
    protected function provider(): AIProviderInterface
    {
        return ProviderFactory::make();
    }

    protected function embeddings(): EmbeddingsProviderInterface
    {
        return ProviderFactory::embeddings();
    }

    protected function vectorStore(): VectorStoreInterface
    {
        $directory = \dirname(__DIR__, 2) . '/storage';

        if (!\is_dir($directory)) {
            \mkdir($directory, 0o755, true);
        }

        return new FileVectorStore(
            directory: $directory,
            name: 'docs',
        );
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You answer questions about the NeuronAI framework using only the '
                . 'documentation excerpts supplied to you.',
            ],
            steps: [
                'Read the retrieved context before answering.',
                'If the context does not contain the answer, say so plainly.',
            ],
            output: [
                'Answer in at most three sentences.',
                'Never state anything the context does not support.',
            ],
        );
    }
}
