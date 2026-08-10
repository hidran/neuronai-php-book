<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Workflow\Interrupt\WorkflowInterrupt;
use NeuronAI\Workflow\Persistence\FilePersistence;
use NeuronBook\Ch15\PublishWorkflow;

/*
 * Section 15.3 - start the workflow and catch the interrupt.
 *
 *   php chapters/Ch15/run/start.php
 *
 * No model needed: the interrupt machinery is pure graph execution.
 *
 * NOTE the namespace. WorkflowInterrupt is in NeuronAI\Workflow\Interrupt,
 * not NeuronAI\Workflow\Exceptions.
 */

$storage = \dirname(__DIR__, 3) . '/storage/workflows';

if (!\is_dir($storage)) {
    \mkdir($storage, 0o755, true);
}

$workflow = new PublishWorkflow(new FilePersistence($storage));

try {
    $state = $workflow->init()->run();

    echo "Completed without interruption: " . \var_export($state->get('outcome'), true) . "\n";
} catch (WorkflowInterrupt $interrupt) {
    $id = $interrupt->getWorkflowId();
    $request = $interrupt->getRequest();

    \file_put_contents(
        $storage . "/pending-{$id}.json",
        \json_encode($request, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR),
    );

    echo "Interrupted, awaiting a human decision.\n";
    echo "  Workflow ID : {$id}\n";
    echo "  Request     : " . \json_encode($request, \JSON_THROW_ON_ERROR) . "\n\n";
    echo "Approve it with:\n";
    echo "  php chapters/Ch15/run/resume.php {$id} approve\n";
}
