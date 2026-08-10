<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Workflow\Workflow;
use NeuronBook\Ch14\ContentWorkflowState;
use NeuronBook\Ch14\ReviewNode;
use NeuronBook\Ch14\WriteNode;

/*
 * Section 14.2 and 14.4 - a bounded loop over a typed state object.
 *
 *   php chapters/Ch14/run/loop.php
 *
 * Custom state is supplied through the Workflow constructor's third parameter,
 * not through init(). init() takes only an optional resume request.
 */

$handler = Workflow::make(
    persistence: null,
    resumeToken: null,
    state: new ContentWorkflowState(),
)
    ->addNodes([
        new WriteNode(),
        new ReviewNode(),
    ])
    ->init();

$state = $handler->run();

echo "\nFinal: " . \var_export($state->get('final'), true) . "\n";
echo 'Escalated: ' . \var_export($state->get('escalate_reason', 'no'), true) . "\n";
