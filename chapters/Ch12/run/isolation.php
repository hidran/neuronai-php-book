<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\RAG\Document;
use NeuronAI\RAG\Schema\DocumentField;
use NeuronAI\RAG\Schema\DocumentSchema;
use NeuronAI\RAG\Schema\DocumentSchemaException;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\Filter\Filter;
use NeuronAI\RAG\VectorStore\SearchRequest;
use NeuronBook\Support\ProviderFactory;

/*
 * Sections 12.5 and 12.6 - proving tenant isolation (Chapter Exercise 2).
 *
 *   php chapters/Ch12/run/isolation.php
 *
 * Two tenants' documents go into ONE store. A search scoped to tenant "acme"
 * must never return a "globex" document, however well it matches. The store
 * is a throwaway file in the system temp directory.
 */

if (!ProviderFactory::isAvailable()) {
    exit("Provider not reachable. Start Ollama, or set a cloud key in .env.\n");
}

$embeddings = ProviderFactory::embeddings();

$directory = \sys_get_temp_dir() . '/neuron-book-ch12';
$name = 'isolation-' . \getmypid();

$store = new FileVectorStore(
    directory: $directory,
    name: $name,
    schema: DocumentSchema::of(
        DocumentField::string('tenant_id')->required()->filterable(),
        DocumentField::string('visibility')->required()->filterable(),
    ),
);

$texts = [
    ['acme',   'public',   'Acme refunds are accepted within 30 days of delivery.'],
    ['acme',   'internal', 'Acme staff may approve refunds up to 500 euros without sign-off.'],
    ['globex', 'public',   'Globex refunds are accepted within 14 days of delivery.'],
];

$documents = [];

foreach ($texts as [$tenant, $visibility, $text]) {
    $documents[] = (new Document($text))
        ->setSourceType('policies')
        ->setSourceName("{$tenant}-refunds")
        ->addMetadata('tenant_id', $tenant)
        ->addMetadata('visibility', $visibility);
}

$store->addDocuments($embeddings->embedDocuments($documents));

$query = $embeddings->embedText('How many days do I have to ask for a refund?');

// 1. Tenant scope: only acme's documents may come back.
$results = $store->search(new SearchRequest(
    embedding: $query,
    filters: Filter::where('tenant_id', 'acme')->where('visibility', 'public'),
    topK: 10,
));

foreach ($results as $document) {
    \printf("acme/public  -> %.3f  %s\n", $document->getScore() ?? 0.0, $document->getContent());

    if ($document->getMetadata()['tenant_id'] !== 'acme') {
        exit("LEAK: a globex document crossed the tenant filter.\n");
    }
}

// 2. Filtering on a field the schema does not declare fails loudly.
try {
    $store->search(new SearchRequest($query, filters: Filter::eq('owner', 'alice')));
    exit("Expected a DocumentSchemaException for an undeclared field.\n");
} catch (DocumentSchemaException $e) {
    echo "undeclared   -> {$e->getMessage()}\n";
}

// 3. Deleting by source removes exactly that source.
$store->delete(Filter::where('sourceType', 'policies')->where('sourceName', 'globex-refunds'));

$left = $store->search(new SearchRequest($query, topK: 10));
\printf("after delete -> %d document(s) left\n", \count($left));

@\unlink("{$directory}/{$name}.store");

echo "Isolation holds.\n";
