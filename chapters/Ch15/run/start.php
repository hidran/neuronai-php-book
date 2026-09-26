<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Workflow\Persistence\FilePersistence;
use NeuronBook\Ch15\PublishWorkflow;

/*
 * Section 15.4 - start the workflow and detect the pause.
 *
 *   php chapters/Ch15/run/start.php
 *
 * No model needed: the interrupt machinery is pure graph execution.
 *
 * Nothing is thrown. run() returns normally and the state says whether the
 * run finished or is suspended, waiting for a human.
 */

$storage = \dirname(__DIR__, 3) . '/storage/workflows';

$workflow = PublishWorkflow::make()
    ->setPersistence(new FilePersistence($storage));

$state = $workflow->run();

if (!$state->isInterrupted()) {
    echo 'Completed without interruption: ' . \var_export($state->get('outcome'), true) . "\n";
    exit(0);
}

$request = $state->getInterruptRequest();
$workflowId = $state->getWorkflowId();

\file_put_contents(
    $storage . "/pending-{$workflowId}.json",
    \json_encode($request, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR),
);

echo "Suspended, awaiting a human decision.\n";
echo "  Workflow ID : {$workflowId}\n";
echo '  Request     : ' . \json_encode($request, \JSON_THROW_ON_ERROR) . "\n\n";
echo "Decide with:\n";
echo "  php chapters/Ch15/run/resume.php {$workflowId} approve\n";
echo "  php chapters/Ch15/run/resume.php {$workflowId} reject\n";
