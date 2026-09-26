<?php

declare(strict_types=1);

namespace NeuronBook\Ch22;

use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

/**
 * Lab 16 - prepare the case the manager will read.
 *
 * In the application this is an agent reading the order and the policy. Here
 * it is a closure that prints a line, so you can see it run exactly once
 * across the start and every resume: the step is committed before the
 * interrupt, and a completed step is never re-executed.
 */
class PrepareCaseNode extends Node
{
    public function __invoke(StartEvent $event, WorkflowState $state): CaseReady
    {
        $orderId = (int) $state->get('order_id');
        $amount = (float) $state->get('requested_amount');

        $summary = $this->memoize('case-summary', static function () use ($orderId, $amount): string {
            echo "  (preparing the case - this line must print only once)\n";

            return \sprintf('Order %d: item arrived damaged, refund of %.2f requested.', $orderId, $amount);
        });

        return new CaseReady($orderId, $amount, $summary);
    }
}
