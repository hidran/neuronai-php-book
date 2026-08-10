# Retrieval-augmented generation

RAG searches a knowledge base for passages relevant to a question and adds them
to the prompt. It hands the model the relevant page. It does not teach the model
anything, and it is not fine-tuning.

## Chunking

A chunk is a piece of a document, produced by a splitter and embedded
independently. Chunk size and the separator are the two parameters that affect
retrieval quality most. Splitting on structure - headings, sections - beats
splitting on a fixed character count, because a chunk that straddles two topics
retrieves badly for both.

## Embeddings

An embedding is a list of numbers representing the meaning of a piece of text.
It is specific to the model that produced it: changing the embeddings model
invalidates every vector already stored, so a re-index is mandatory.

## Source names

The `sourceName` metadata is what makes `reindexBySource()` work. It must be
stable - a record ID, never a title, because titles get edited.

## Reranking

Reranking re-scores retrieved candidates with a model that reads the query and
each document together. Retrieve fifty, rerank, send five. It is the
highest-return improvement available to a RAG system that already works.
