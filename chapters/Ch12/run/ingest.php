<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\Splitter\SentenceTextSplitter;
use NeuronBook\Ch12\DocsAgent;
use NeuronBook\Support\ProviderFactory;

/*
 * Section 12.3 - ingestion.
 *
 *   php chapters/Ch12/run/ingest.php
 *
 * Reads chapters/Ch12/docs/, splits it, embeds each chunk with
 * nomic-embed-text through Ollama, and writes the vectors to storage/.
 *
 * reindexBySource() rather than addDocuments(): re-running this should replace
 * what was there, not append a second copy of every chunk. That is what the
 * sourceName metadata is for.
 */

if (!ProviderFactory::isAvailable()) {
    exit("Provider not reachable. Start Ollama, or set a cloud key in .env.\n");
}

$documents = FileDataLoader::for(__DIR__ . '/../docs')
    ->withSplitter(new SentenceTextSplitter(maxWords: 200, overlapWords: 20))
    ->getDocuments();

\printf("Loaded %d chunk(s).\n", \count($documents));

/*
 * Bug in neuron-ai 3.16.4, worth knowing about before it bites you.
 *
 * reindexBySource() calls FileVectorStore::deleteBy(), which streams the store
 * through FileVectorStore::getLine(). getLine() calls fopen() with no
 * existence check, so on a store that has never been written the fopen returns
 * false and fclose(false) throws a TypeError:
 *
 *   TypeError: fclose(): Argument #1 ($stream) must be of type resource,
 *   false given in .../RAG/VectorStore/FileVectorStore.php:161
 *
 * In other words the recommended re-indexing call cannot be the *first* call
 * you make. Creating the empty store file first is enough to satisfy it.
 */
$storeFile = \dirname(__DIR__, 3) . '/storage/docs.store';

if (!\is_file($storeFile)) {
    \touch($storeFile);
}

DocsAgent::make()->reindexBySource($documents);

echo "Indexed into storage/docs.store\n";
