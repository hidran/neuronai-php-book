<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Workflow\Workflow;
use NeuronBook\Ch13\InitialNode;
use NeuronBook\Ch13\NodeOne;
use NeuronBook\Ch13\NodeTwo;

/*
 * Section 13.3 - a three-node workflow.
 *
 *   php chapters/Ch13/run/workflow.php
 *
 * No model, no network, no API key. A Workflow is a graph executor; the fact
 * that most of the interesting nodes call an LLM is incidental to the machinery.
 */

$handler = Workflow::make()
    ->addNodes([
        new InitialNode(),
        new NodeOne(),
        new NodeTwo(),
    ])
    ->init();

$state = $handler->run();

echo "\nFinal state: " . \var_export($state->get('answer'), true) . "\n";
