<?php

declare(strict_types=1);

namespace NeuronBook\Ch15;

use NeuronAI\Agent\Interrupt\ApprovalRequest;
use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Interrupt\Action;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

/**
 * Chapter 15 - human in the loop.
 *
 * interrupt() suspends the run. Everything above it has already run;
 * everything below it runs later, in a different process, possibly days later.
 * run() does not throw: it returns a state whose isInterrupted() is true.
 *
 * On resume the node runs again FROM THE TOP, and this time interrupt()
 * returns the payload the caller passed to resume(). memoize() is what makes
 * that safe: the closure's result is persisted, so the resumed node gets the
 * stored value instead of re-running the closure (Section 15.5).
 *
 * ApprovalRequest lives in NeuronAI\Agent\Interrupt; Action stays in
 * NeuronAI\Workflow\Interrupt. Action is a read-only outbound value object:
 * the decision comes back as a plain array, never by mutating the Action.
 */
class ApprovalNode extends Node
{
    public function __invoke(StartEvent $event, WorkflowState $state): StopEvent
    {
        // Stands in for an expensive LLM call. Run once, replayed on resume.
        $proposal = $this->memoize('draft-proposal', static function (): string {
            echo "  (generating the proposal - this line must print only once)\n";

            return 'Delete /var/log/old.txt (4.2 GB, last modified 2019)';
        });

        $state->set('proposal', $proposal);

        $payload = $this->interrupt(
            new ApprovalRequest(
                message: 'Approve deleting a 4.2 GB log file untouched since 2019?',
                actions: [
                    new Action('delete_file', 'Delete File', $proposal),
                ],
            )
        );

        // The payload's shape is your contract with whoever resumes the run.
        // This book uses the agent's own convention:
        //   'approve' | 'reject' | ['reject', 'reason']
        $decision = $payload['delete_file'] ?? 'reject';

        if ($decision === 'approve') {
            $state->set('outcome', 'deleted');
        } else {
            $state->set('outcome', 'declined');
            $state->set('operator_note', \is_array($decision) ? ($decision[1] ?? null) : null);
        }

        return new StopEvent();
    }
}
