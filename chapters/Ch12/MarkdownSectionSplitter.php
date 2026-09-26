<?php

declare(strict_types=1);

namespace NeuronBook\Ch12;

use NeuronAI\RAG\Document;
use NeuronAI\RAG\Splitter\SplitterInterface;

/**
 * Section 12.3 - a heading-aware splitter for Markdown.
 *
 * Every chunk is one "## " section with its heading. Oversized sections fall
 * back to paragraph splitting. Each chunk inherits the parent's sourceType,
 * sourceName and metadata - without that, reindexBySource() could never find
 * the chunks again, because every one would be filed under "manual".
 */
class MarkdownSectionSplitter implements SplitterInterface
{
    public function __construct(
        private readonly int $maxChars = 2000,
    ) {}

    public function splitDocument(Document $document): array
    {
        // Split on level-2 headings, keeping the heading with its section
        $parts = \preg_split(
            '/^(?=##\s)/m',
            $document->getContent(),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $chunks = [];

        foreach ($parts as $part) {
            $part = \trim($part);

            if ($part === '') {
                continue;
            }

            // Fall back to paragraph splitting for oversized sections
            if (\mb_strlen($part) > $this->maxChars) {
                foreach ($this->splitLongSection($part) as $piece) {
                    $chunks[] = $piece;
                }
                continue;
            }

            $chunks[] = $part;
        }

        // Each chunk keeps its parent's provenance and metadata
        return \array_map(
            fn (string $text): Document => (new Document($text))
                ->setSourceType($document->getSourceType())
                ->setSourceName($document->getSourceName())
                ->setMetadata($document->getMetadata()),
            $chunks
        );
    }

    public function splitDocuments(array $documents): array
    {
        $result = [];

        foreach ($documents as $document) {
            foreach ($this->splitDocument($document) as $chunk) {
                $result[] = $chunk;
            }
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function splitLongSection(string $section): array
    {
        $paragraphs = \preg_split('/\n{2,}/', $section) ?: [];

        $chunks  = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            if (\mb_strlen($current . "\n\n" . $paragraph) > $this->maxChars && $current !== '') {
                $chunks[] = \trim($current);
                $current  = $paragraph;
                continue;
            }

            $current = $current === '' ? $paragraph : $current . "\n\n" . $paragraph;
        }

        if (\trim($current) !== '') {
            $chunks[] = \trim($current);
        }

        return $chunks;
    }
}
