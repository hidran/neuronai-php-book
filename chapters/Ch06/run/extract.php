<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Chat\Messages\UserMessage;
use NeuronBook\Ch06\ExtractorAgent;
use NeuronBook\Ch06\Person;
use NeuronBook\Support\ProviderFactory;

/*
 * Section 6.3 - structured() returns a typed, validated instance of your class,
 * not a string you have to json_decode and pray over.
 *
 *   php chapters/Ch06/run/extract.php "I'm John and I want pizza at 12 James Street, 00560 Rome"
 */

if (!ProviderFactory::isAvailable()) {
    exit("Provider not reachable. Start Ollama, or set a cloud key in .env.\n");
}

$text = $argv[1] ?? "I'm John and I want a pizza delivered to 12 James Street, 00560 Rome.";

$person = ExtractorAgent::make()->structured(
    messages: new UserMessage($text),
    class: Person::class,
    maxRetries: 3,
);

\assert($person instanceof Person);

\printf("name       : %s\n", $person->name);
\printf("preference : %s\n", $person->preference);

if ($person->address !== null) {
    \printf("street     : %s\n", $person->address->street);
    \printf("city       : %s\n", $person->address->city);
    \printf("zip        : %s\n", $person->address->zip);
}
