<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

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
 * Local Ollama text models cannot read a PDF, so set NEURON_PROVIDER
 * accordingly before running this one.
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
        mediaType: 'application/pdf',
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
$computed = \array_sum(\array_map(static fn (object $l): float => $l->line_total, $invoice->lines));

if (\abs($computed - $invoice->subtotal) > 0.01) {
    \fwrite(STDERR, \sprintf(
        "Line totals sum to %.2f but subtotal reads %.2f - flag for human review\n",
        $computed,
        $invoice->subtotal,
    ));
}
