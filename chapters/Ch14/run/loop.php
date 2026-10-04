<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Workflow\Workflow;
use NeuronBook\Ch14\ContentWorkflowState;
use NeuronBook\Ch14\ReviewNode;
use NeuronBook\Ch14\WriteNode;

/*
 * Sections 14.1 and 14.3 - a bounded loop over a typed state object.
 *
 *   php chapters/Ch14/run/loop.php
 *
 * The Workflow constructor is (?string $workflowId, ?WorkflowState $state), so
 * a custom state goes in by name: Workflow::make(workflowId: ..., state: ...). A
 * Workflow subclass can instead return it from its state() hook.
 */

$state = Workflow::make(workflowId: 'demo', state: new ContentWorkflowState())
    ->addNodes([
        new WriteNode(),
        new ReviewNode(),
    ])
    ->run();

echo "\nFinal: " . \var_export($state->get('final'), true) . "\n";
echo 'Escalated: ' . \var_export($state->get('escalate_reason', 'no'), true) . "\n";
