<?php

declare(strict_types=1);

// The runner loads this file from the project root before it runs any
// evaluator, so this is where .env gets loaded. Without it, the evaluators
// would ignore NEURON_PROVIDER and friends unless every command also passed
// --autoload-file=bootstrap.php.
require_once __DIR__ . '/bootstrap.php';

use NeuronAI\Evaluation\Output\ConsoleOutput;
use NeuronAI\Evaluation\Output\JsonOutput;

/*
 * Evaluation output configuration - Section 10.6.
 *
 * IMPORTANT, and different from what the book's first edition prints:
 *
 *   'output' is a LIST, not a class => options map.
 *
 * EvaluationOutputResolver accepts either a class-string of a zero-argument
 * EvaluationOutputInterface, or an already-constructed instance. Its own
 * docblock is explicit: "There is no reflection-based option mapping or
 * callable factory." A driver that needs constructor arguments must therefore
 * be handed over as an instance, exactly as JsonOutput is below.
 *
 * The class names are ConsoleOutput and JsonOutput, in
 * NeuronAI\Evaluation\Output - not ConsoleDriver/JsonDriver in
 * NeuronAI\Evaluation\OutputDrivers.
 */

return [
    'output' => [
        ConsoleOutput::class,
        new JsonOutput(__DIR__ . '/evaluation-results.json'),
    ],
];
