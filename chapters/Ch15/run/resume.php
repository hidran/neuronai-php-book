<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Exceptions\WorkflowException;
use NeuronAI\Workflow\Executor\ExecutionRequest;
use NeuronAI\Workflow\Persistence\FilePersistence;
use NeuronBook\Ch15\PublishWorkflow;

/*
 * Section 15.4 - resume, in a different process, with the human's decision.
 *
 *   php chapters/Ch15/run/resume.php <workflow-id> [approve|reject]
 *
 * Three things must match the run that suspended: the workflow class (so the
 * graph is identical), the persistence backend, and the workflow ID. The
 * decision itself is a plain array - no request object to rebuild. The
 * memoized proposal above the interrupt is NOT regenerated.
 */

$workflowId = $argv[1] ?? null;
$decision = $argv[2] ?? 'approve';

if ($workflowId === null) {
    \fwrite(STDERR, "Usage: php chapters/Ch15/run/resume.php <workflow-id> [approve|reject]\n");
    exit(1);
}

$storage = \dirname(__DIR__, 3) . '/storage/workflows';

$payload = [
    'delete_file' => $decision === 'approve'
        ? 'approve'
        : ['reject', 'Keep it until the audit closes.'],
];

try {
    $state = PublishWorkflow::make(workflowId: $workflowId)
        ->setPersistence(new FilePersistence($storage))
        ->run(ExecutionRequest::resume($payload));
} catch (WorkflowException $e) {
    // A finished run cleans up after itself, so resuming it twice - or
    // resuming an ID that never paused - lands here: "No run in flight".
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

@\unlink($storage . "/pending-{$workflowId}.json");

echo "Resumed and finished.\n";
echo '  proposal : ' . \var_export($state->get('proposal'), true) . "\n";
echo '  outcome  : ' . \var_export($state->get('outcome'), true) . "\n";
echo '  note     : ' . \var_export($state->get('operator_note'), true) . "\n";
