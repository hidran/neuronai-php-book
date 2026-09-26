<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Chat\Enums\MediaType;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronBook\Ch08\Invoice;
use NeuronBook\Ch08\InvoiceAgent;

/*
 * Section 8.4 - a PDF in, a typed Invoice out.
 *
 *   php chapters/Ch08/run/extract-invoice.php /path/to/invoice.pdf
 *
 * Needs a provider that accepts documents: Anthropic, OpenAI or Gemini.
 * The Ollama mapper sends text and base64 images only - a FileContent block
 * is dropped from the request, and the model would answer without ever
 * seeing the invoice. Set NEURON_PROVIDER accordingly before running this.
 *
 * NOTE: SourceType lives in NeuronAI\Chat\Enums - it is easy to miss because
 * the content-block classes it is used with live in a different namespace.
 */

$path = $argv[1] ?? null;

if ($path === null || !\is_file($path)) {
    \fwrite(STDERR, "Usage: php chapters/Ch08/run/extract-invoice.php <invoice.pdf>\n");
    exit(1);
}

$bytes = \file_get_contents($path);

if ($bytes === false) {
    \fwrite(STDERR, "Could not read {$path}\n");
    exit(1);
}

$message = new UserMessage('Extract the structured data from this invoice.');

// The first constructor parameter is $content, not $source.
$message->addContent(
    new FileContent(
        content: \base64_encode($bytes),
        sourceType: SourceType::BASE64,
        mediaType: MediaType::PDF,
        filename: \basename($path),
    )
);

$invoice = InvoiceAgent::make()->structured(
    messages: $message,
    maxRetries: 2,
);

\assert($invoice instanceof Invoice);

\printf(
    "Invoice %s from %s (%s)\n  Subtotal: %.2f\n  VAT:      %.2f\n  Total:    %.2f\n  Lines:    %d\n",
    $invoice->number,
    $invoice->supplier_name,
    $invoice->issue_date,
    $invoice->subtotal,
    $invoice->vat_amount,
    $invoice->total,
    \count($invoice->lines),
);

// Section 8.5 - the model transcribes; your code checks the arithmetic.
// PHP 8.5: the pipe reads in order - take the line totals, then add them up.
$computed = \array_column($invoice->lines, 'line_total') |> \array_sum(...);

if (\abs($computed - $invoice->subtotal) > 0.01) {
    \fwrite(STDERR, \sprintf(
        "Line totals sum to %.2f but subtotal reads %.2f - flag for human review\n",
        $computed,
        $invoice->subtotal,
    ));
}
