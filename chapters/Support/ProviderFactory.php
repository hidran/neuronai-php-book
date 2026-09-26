<?php

declare(strict_types=1);

namespace NeuronBook\Support;

use InvalidArgumentException;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Providers\Gemini\Gemini;
use NeuronAI\Providers\Mistral\Mistral;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use NeuronAI\RAG\Embeddings\OpenAIEmbeddingsProvider;

/**
 * One place that knows how to build a provider.
 *
 * This is the pattern Chapter 3 argues for (Section 3.6, "Make the provider
 * configurable") and Chapter 4 extends with contextWindow(). Every agent in
 * this repository calls it instead of instantiating a provider inline, so the
 * whole book's worth of examples can be pointed at a different model by
 * changing one environment variable.
 */
final class ProviderFactory
{
    public const DEFAULT_DRIVER = 'ollama';

    public static function make(?string $driver = null): AIProviderInterface
    {
        $driver ??= Env::get('NEURON_PROVIDER', self::DEFAULT_DRIVER);

        return match ($driver) {
            'anthropic' => new Anthropic(
                key: Env::require('ANTHROPIC_API_KEY'),
                model: Env::get('ANTHROPIC_MODEL', 'claude-sonnet-4-5') ?? '',
            ),
            'openai' => new OpenAI(
                key: Env::require('OPENAI_API_KEY'),
                model: Env::get('OPENAI_MODEL', 'gpt-4.1-mini') ?? '',
            ),
            'gemini' => new Gemini(
                key: Env::require('GEMINI_API_KEY'),
                model: Env::get('GEMINI_MODEL', 'gemini-2.0-flash') ?? '',
            ),
            'mistral' => new Mistral(
                key: Env::require('MISTRAL_API_KEY'),
                model: Env::get('MISTRAL_MODEL', 'mistral-large-latest') ?? '',
            ),
            'ollama' => new Ollama(
                url: Env::get('OLLAMA_URL', 'http://localhost:11434/api') ?? '',
                model: Env::get('OLLAMA_MODEL', 'llama3.2') ?? '',
            ),
            default => throw new InvalidArgumentException("Unknown provider driver [{$driver}]."),
        };
    }

    public static function embeddings(?string $driver = null): EmbeddingsProviderInterface
    {
        $driver ??= Env::get('NEURON_PROVIDER', self::DEFAULT_DRIVER);

        return match ($driver) {
            'openai' => new OpenAIEmbeddingsProvider(
                key: Env::require('OPENAI_API_KEY'),
                model: Env::get('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small') ?? '',
            ),
            default => new OllamaEmbeddingsProvider(
                model: Env::get('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text') ?? '',
                url: Env::get('OLLAMA_URL', 'http://localhost:11434/api') ?? '',
            ),
        };
    }

    /**
     * Context window to configure the history trimmer with - deliberately 5-10%
     * under each model's real limit, for the reason Section 4.4 gives: the
     * trimmer needs headroom to choose a sensible cut point.
     */
    public static function contextWindow(?string $driver = null): int
    {
        $driver ??= Env::get('NEURON_PROVIDER', self::DEFAULT_DRIVER);

        return match ($driver) {
            'anthropic' => 185_000,
            'openai', 'mistral' => 118_000,
            'gemini' => 920_000,
            default => 29_000,
        };
    }

    /**
     * True when the configured provider can actually be reached right now.
     * The runnable scripts use this to skip with a helpful message instead of
     * dying on a connection error.
     */
    public static function isAvailable(?string $driver = null): bool
    {
        $driver ??= Env::get('NEURON_PROVIDER', self::DEFAULT_DRIVER);

        if ($driver === 'ollama') {
            $url = Env::get('OLLAMA_URL', 'http://localhost:11434/api') ?? '';
            $context = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);

            return @file_get_contents(rtrim($url, '/') . '/tags', false, $context) !== false;
        }

        return match ($driver) {
            'anthropic' => Env::has('ANTHROPIC_API_KEY'),
            'openai' => Env::has('OPENAI_API_KEY'),
            'gemini' => Env::has('GEMINI_API_KEY'),
            'mistral' => Env::has('MISTRAL_API_KEY'),
            default => false,
        };
    }
}
