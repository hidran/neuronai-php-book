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
 * sourceName metadata is for. It is safe as the very first call, too:
 * FileVectorStore creates an empty store file in its constructor, so the
 * delete-then-add inside reindexBySource() has something to read.
 */

if (!ProviderFactory::isAvailable()) {
    exit("Provider not reachable. Start Ollama, or set a cloud key in .env.\n");
}

$documents = FileDataLoader::for(__DIR__ . '/../docs')
    ->withSplitter(new SentenceTextSplitter(maxWords: 200, overlapWords: 20))
    ->getDocuments();

\printf("Loaded %d chunk(s).\n", \count($documents));

DocsAgent::make()->reindexBySource($documents);

echo "Indexed into storage/docs.store\n";
