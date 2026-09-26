<?php

declare(strict_types=1);

namespace NeuronBook\Ch22;

use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

/**
 * Lab 16 - act on the decision.
 *
 * The real refund call belongs here, with an idempotency key derived from the
 * order: memoize() reuses a committed result, but it cannot make an uncertain
 * external side effect exactly-once.
 */
class SettleRefundNode extends Node
{
    public function __invoke(RefundDecided $event, WorkflowState $state): StopEvent
    {
        $state->set('resolution', $event->resolution);
        $state->set('refunded', \in_array($event->resolution, ['approved', 'auto-approved'], true) ? $event->amount : 0.0);
        $state->set('feedback', $event->feedback);

        return new StopEvent();
    }
}
