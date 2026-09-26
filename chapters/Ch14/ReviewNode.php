<?php

declare(strict_types=1);

namespace NeuronBook\Ch14;

use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Node;

/**
 * Section 14.1 - a loop, and the bound that keeps it from being infinite.
 *
 * Returning StartEvent sends control back to the node that consumes
 * StartEvent - the writer - which reads the reviewer's feedback from state and
 * produces the next draft. That is the whole looping mechanism: there is no
 * loop construct, only an event that routes backwards.
 *
 * Which is exactly why the attempt counter is not optional. An agent that
 * disagrees with its reviewer forever is a bill, not a bug report.
 *
 * The node type-hints ContentWorkflowState directly: the second parameter of
 * __invoke() may be any WorkflowState subclass, so no assert() is needed.
 */
class ReviewNode extends Node
{
    private const MAX_ATTEMPTS = 3;

    public function __invoke(DraftReady $event, ContentWorkflowState $state): StartEvent|StopEvent
    {
        // Stands in for a ReviewerAgent::make()->structured(...) call, so the
        // example runs with no provider. The control flow is the point.
        $approved = $state->revisionCount() >= 2;

        if ($approved) {
            echo "- ReviewNode approved: {$event->draft}\n";
            $state->set('final', $event->draft);

            return new StopEvent();
        }

        if ($state->hasReachedLimit(self::MAX_ATTEMPTS)) {
            echo "- ReviewNode hit the attempt limit, escalating\n";
            $state->set('escalate_reason', 'review_limit_reached');
            $state->set('final', $event->draft);

            return new StopEvent();
        }

        $state->addRevision($event->draft, 'Tighten the opening paragraph.');
        echo "- ReviewNode rejected (attempt {$state->revisionCount()})\n";

        return new StartEvent();
    }
}
