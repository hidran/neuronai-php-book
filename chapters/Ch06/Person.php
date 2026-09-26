<?php

declare(strict_types=1);

namespace NeuronBook\Ch06;

use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;

/**
 * Section 6.2 - the DTO *is* the schema.
 *
 * The attributes are not documentation. They are compiled into the JSON Schema
 * sent to the model, and the validation rules are re-checked on the way back:
 * a violation triggers a retry that tells the model exactly what was wrong.
 */
class Person
{
    #[SchemaProperty(description: 'The user name.', required: true)]
    #[NotBlank]
    public string $name;

    // required: true only shapes the schema sent to the model; it is not
    // checked on the way back. #[NotBlank] is what makes an omitted key
    // trigger the retry instead of an uninitialised property.
    #[SchemaProperty(description: 'What the user loves to eat.', required: true)]
    #[NotBlank]
    public string $preference;

    #[SchemaProperty(description: 'The address to complete the delivery.', required: false)]
    public ?Address $address = null;
}
