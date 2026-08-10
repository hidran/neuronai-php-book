<?php

declare(strict_types=1);

namespace NeuronBook\Ch08;

use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
use NeuronAI\StructuredOutput\Validation\Rules\Regex;

/**
 * Section 8.4 - the document-extraction capstone.
 *
 * Every description is written for a model that has never seen your invoices.
 * "Do not reformat it" and "Convert from whatever format appears on the
 * document" are instructions, not commentary - they are the only place the
 * extraction rules can live.
 */
class Invoice
{
    #[SchemaProperty(
        description: 'The invoice number exactly as printed. Do not reformat it.',
        required: true
    )]
    #[NotBlank]
    public string $number;

    #[SchemaProperty(
        description: 'Issue date in ISO 8601 format, YYYY-MM-DD. Convert from whatever '
                   . 'format appears on the document.',
        required: true
    )]
    #[Regex('/^\d{4}-\d{2}-\d{2}$/')]
    public string $issue_date;

    #[SchemaProperty(description: 'The legal name of the supplier issuing the invoice.', required: true)]
    #[NotBlank]
    public string $supplier_name;

    #[SchemaProperty(
        description: 'Three-letter ISO currency code, e.g. EUR, USD, GBP.',
        required: true
    )]
    #[Regex('/^[A-Z]{3}$/')]
    public string $currency;

    #[SchemaProperty(description: 'Total excluding VAT.', required: true)]
    public float $subtotal;

    #[SchemaProperty(description: 'VAT amount.', required: true)]
    public float $vat_amount;

    #[SchemaProperty(description: 'Total including VAT.', required: true)]
    public float $total;

    /**
     * @var InvoiceLine[]
     */
    #[SchemaProperty(
        description: 'Every line item on the invoice.',
        required: true,
        anyOf: [InvoiceLine::class]
    )]
    public array $lines = [];
}
