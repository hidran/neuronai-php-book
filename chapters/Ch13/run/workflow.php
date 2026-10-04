<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Workflow\Workflow;
use NeuronBook\Ch13\InitialNode;
use NeuronBook\Ch13\NodeOne;
use NeuronBook\Ch13\NodeTwo;

/*
 * Section 13.4 - a three-node workflow.
 *
 *   php chapters/Ch13/run/workflow.php
 *
 * No model, no network, no API key. A Workflow is a graph executor; the fact
 * that most of the interesting nodes call an LLM is incidental to the machinery.
 *
 * run() is called on the workflow itself and returns the final WorkflowState.
 * There is no handler object and no init() step in between. The workflow ID
 * must be bound before the run: the framework never makes one up.
 */

$state = Workflow::make(workflowId: 'demo')
    ->addNodes([
        new InitialNode(),
        new NodeOne(),
        new NodeTwo(),
    ])
    ->run();

echo "\nFinal state: " . \var_export($state->get('answer'), true) . "\n";
echo 'Status: ' . $state->getStatus()->name . "\n";
