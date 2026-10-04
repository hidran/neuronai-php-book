<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Exceptions\RunInFlightException;
use NeuronAI\Exceptions\StaleWorkflowRunException;
use NeuronAI\Workflow\Executor\ExecutionRequest;
use NeuronAI\Workflow\Persistence\FilePersistence;
use NeuronAI\Workflow\WorkflowState;
use NeuronBook\Ch22\RefundWorkflow;

/*
 * Chapter 22 - the refund approval lifecycle, without Laravel.
 *
 *   php chapters/Ch22/run/refund.php
 *
 * No model needed. Every RefundWorkflow::make() below is a fresh instance
 * holding nothing but an order ID - exactly what a queue job has when it
 * resumes a run another process started. FilePersistence stands in for
 * EloquentPersistence(WorkflowStore::class).
 */

$storage = \dirname(__DIR__, 3) . '/storage/workflows/ch22';
$persistence = new FilePersistence($storage);

// Start from a clean store so the script can be run repeatedly.
foreach (\glob($storage . '/*') ?: [] as $file) {
    \unlink($file);
}

$report = static function (string $label, WorkflowState $state): void {
    \printf(
        "  %-26s status=%s resolution=%s refunded=%s\n",
        $label,
        $state->getStatus()->value,
        \var_export($state->get('resolution'), true),
        \var_export($state->get('refunded'), true),
    );
};

echo "1. A 40.00 refund never asks anyone\n";
$state = RefundWorkflow::make(orderId: 1001, requestedAmount: 40.0)
    ->setPersistence($persistence)
    ->run();
$report('run()', $state);

echo "\n2. A 400.00 refund waits for a manager\n";
$state = RefundWorkflow::make(orderId: 1002, requestedAmount: 400.0)
    ->setPersistence($persistence)
    ->run();

// What the application stores: the projection it needs to route the answer.
$runId = (string) $state->getRunId();
$attempt = (int) $state->getExecutionAttempt();

\printf("  suspended: workflow=%s run=%s attempt=%d\n", $state->getWorkflowId(), $runId, $attempt);
echo '  request: ' . \json_encode($state->getInterruptRequest(), \JSON_THROW_ON_ERROR) . "\n";

try {
    RefundWorkflow::make(orderId: 1002, requestedAmount: 400.0)->setPersistence($persistence)->run();
} catch (RunInFlightException $e) {
    echo "  second run() refused: RunInFlightException ({$e->status->value})\n";
}

// The resume job: order ID, decision, and the fences it observed.
$state = RefundWorkflow::make(orderId: 1002)
    ->setPersistence($persistence)
    ->run(ExecutionRequest::resume(
        ['decision' => 'approve', 'amount' => 350.0],
        expectedRunId: $runId,
        expectedExecutionAttempt: $attempt,
    ));
$report('fenced resume()', $state);

// The same job delivered twice (a double click, a queue redelivery).
try {
    RefundWorkflow::make(orderId: 1002)
        ->setPersistence($persistence)
        ->run(ExecutionRequest::resume(
            ['decision' => 'approve'],
            expectedRunId: $runId,
            expectedExecutionAttempt: $attempt,
        ));
} catch (StaleWorkflowRunException $e) {
    echo "  redelivery refused: {$e->getMessage()}\n";
}

echo "\n3. Nobody answers: the deadline is the workflow's, not a cron script's\n";
$state = RefundWorkflow::make(orderId: 1003, requestedAmount: 250.0, approvalWindow: '+1 second')
    ->setPersistence($persistence)
    ->run();
$runId = (string) $state->getRunId();

$state = RefundWorkflow::make(orderId: 1003)->setPersistence($persistence)->run(ExecutionRequest::resume());
$report('resume() before deadline', $state);

\sleep(2);

$state = RefundWorkflow::make(orderId: 1003)
    ->setPersistence($persistence)
    ->run(ExecutionRequest::resume(expectedRunId: $runId));
$report('resume() after deadline', $state);
echo '  feedback: ' . \var_export($state->get('feedback'), true) . "\n";

echo "\n4. A completion that must survive a lost response\n";
$state = RefundWorkflow::make(orderId: 1004, requestedAmount: 60.0)
    ->setPersistence($persistence)
    ->retainCompletionUntilAcknowledged()
    ->run();
$runId = (string) $state->getRunId();
$report('run() retained', $state);

// The worker died before recording the outcome. The retry replays it.
$replayed = RefundWorkflow::make(orderId: 1004)
    ->setPersistence($persistence)
    ->retainCompletionUntilAcknowledged()
    ->run(ExecutionRequest::resume(expectedRunId: $runId));
$report('resume() replay', $replayed);

RefundWorkflow::make(orderId: 1004)->setPersistence($persistence)->acknowledge($runId);
echo "  acknowledged; files left in the store: " . \count(\glob($storage . '/*') ?: []) . "\n";
