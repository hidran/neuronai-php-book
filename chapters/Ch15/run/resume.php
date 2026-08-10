<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Workflow\Interrupt\Action;
use NeuronAI\Workflow\Interrupt\ApprovalRequest;
use NeuronAI\Workflow\Persistence\FilePersistence;
use NeuronBook\Ch15\PublishWorkflow;

/*
 * Section 15.4 - resume, in a different process, with the human's decision.
 *
 *   php chapters/Ch15/run/resume.php <workflow-id> [approve|reject]
 *
 * The second constructor argument is the resume token. Pass the same workflow
 * ID you were handed at interruption and the graph picks up exactly where it
 * stopped - the checkpoint above the interrupt is NOT re-executed.
 */

$id = $argv[1] ?? null;
$decision = $argv[2] ?? 'approve';

if ($id === null) {
    \fwrite(STDERR, "Usage: php chapters/Ch15/run/resume.php <workflow-id> [approve|reject]\n");
    exit(1);
}

$storage = \dirname(__DIR__, 3) . '/storage/workflows';

$action = new Action('delete_file', 'Delete File', 'Delete /var/log/old.txt');

if ($decision === 'approve') {
    $action->approve('Confirmed by the on-call engineer.');
} else {
    $action->reject('Keep it until the audit closes.');
}

$request = new ApprovalRequest(
    message: 'Should I continue?',
    actions: [$action],
);

$workflow = new PublishWorkflow(new FilePersistence($storage), $id);

$state = $workflow->init($request)->run();

echo "Resumed and finished.\n";
echo '  proposal : ' . \var_export($state->get('proposal'), true) . "\n";
echo '  outcome  : ' . \var_export($state->get('outcome'), true) . "\n";
echo '  note     : ' . \var_export($state->get('operator_note'), true) . "\n";
