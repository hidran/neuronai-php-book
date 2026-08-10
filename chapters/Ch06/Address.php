<?php

declare(strict_types=1);

namespace NeuronBook\Ch06;

use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;

/**
 * Section 6.4 - nesting. A typed property whose class carries its own
 * SchemaProperty attributes becomes a nested object in the generated schema.
 */
class Address
{
    #[SchemaProperty(description: 'The name of the street.', required: true)]
    #[NotBlank]
    public string $street;

    #[SchemaProperty(description: 'The name of the city.', required: false)]
    public string $city = '';

    #[SchemaProperty(description: 'The zip code of the address.', required: true)]
    #[NotBlank]
    public string $zip;
}
