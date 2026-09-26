<?php

declare(strict_types=1);

namespace NeuronBook\Ch22;

use NeuronAI\Workflow\NodeInterface;
use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowState;

/**
 * Chapter 22 - the refund workflow, keyed by its order.
 *
 * workflowId() makes the order the continuation handle: any process that can
 * build RefundWorkflow::make(orderId: 42) and reach the same persistence finds
 * the run. It also means one live refund run per order - a second run() while
 * one is suspended throws RunInFlightException.
 *
 * The requested amount only matters on the first run: a continuation restores
 * the persisted state, so it can be built from the order ID alone.
 */
class RefundWorkflow extends Workflow
{
    public function __construct(
        private readonly int $orderId,
        float $requestedAmount = 0.0,
        private readonly string $approvalWindow = '+48 hours',
    ) {
        parent::__construct(state: new WorkflowState([
            'order_id'         => $orderId,
            'requested_amount' => $requestedAmount,
        ]));
    }

    public function workflowId(): ?string
    {
        return 'refund:' . $this->orderId;
    }

    /**
     * @return NodeInterface[]
     */
    protected function nodes(): array
    {
        return [
            new PrepareCaseNode(),
            new ApprovalNode($this->approvalWindow),
            new SettleRefundNode(),
        ];
    }
}
