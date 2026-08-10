<?php

declare(strict_types=1);

namespace NeuronBook\Ch15;

use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Interrupt\Action;
use NeuronAI\Workflow\Interrupt\ApprovalRequest;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

/**
 * Chapter 15 - human in the loop.
 *
 * interrupt() throws a WorkflowInterrupt. Everything above it has already run;
 * everything below it runs later, in a different process, possibly days later.
 * The state and the position in the graph are serialised by the persistence
 * layer and rehydrated on resume.
 *
 * checkpoint() is what makes that safe. The closure's result is stored, so a
 * resumed node does not re-run it. Section 15.6 is emphatic: wrap every LLM
 * call that precedes an interrupt(), or you pay for it twice and may get a
 * different answer the second time.
 */
class ApprovalNode extends Node
{
    public function __invoke(StartEvent $event, WorkflowState $state): StopEvent
    {
        // Stands in for an expensive LLM call. Run once, cached across resume.
        $proposal = $this->checkpoint(
            'draft-proposal',
            static fn (): string => 'Delete /var/log/old.txt (4.2 GB, last modified 2019)',
        );

        \assert(\is_string($proposal));

        $state->set('proposal', $proposal);

        $response = $this->interrupt(
            new ApprovalRequest(
                message: 'Should I continue?',
                actions: [
                    new Action('delete_file', 'Delete File', $proposal),
                ],
            )
        );

        $action = $response?->getAction('delete_file');

        if ($action !== null && $action->isApproved()) {
            $state->set('outcome', 'deleted');

            /*
             * Read the PROPERTY, not the method. Action::feedback() is not a
             * getter - it is declared
             *
             *   public function feedback(?string $feedback = null): ?string
             *   { $this->feedback = $feedback; return $this->feedback; }
             *
             * so calling it with no argument overwrites the stored feedback
             * with null and hands you the null back. Verified in 3.16.4.
             */
            $state->set('operator_note', $action->feedback);
        } else {
            $state->set('outcome', 'declined');
        }

        return new StopEvent();
    }
}
