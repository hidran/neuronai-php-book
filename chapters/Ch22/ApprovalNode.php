<?php

declare(strict_types=1);

namespace NeuronBook\Ch22;

use DateTimeImmutable;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

/**
 * Sections 22.2-22.4 - interrupt above the threshold, with a deadline.
 *
 * interruptIf() returns null in two situations, told apart by the condition:
 * the amount was under the threshold (nobody was asked), or the deadline
 * passed and an inputless run(ExecutionRequest::resume()) delivered no answer. The node never
 * compares clocks: the workflow validated the persisted deadline before
 * re-entering it.
 */
class ApprovalNode extends Node
{
    public const THRESHOLD = 100.0;

    public function __construct(private readonly string $window = '+48 hours')
    {
    }

    public function __invoke(CaseReady $event, WorkflowState $state): RefundDecided
    {
        $payload = $this->interruptIf(
            $event->amount > self::THRESHOLD,
            new RefundApprovalRequest(
                message: $event->summary,
                orderId: $event->orderId,
                amount: $event->amount,
                expiresAt: new DateTimeImmutable($this->window),
            ),
        );

        if ($event->amount <= self::THRESHOLD) {
            return new RefundDecided('auto-approved', $event->amount);
        }

        if ($payload === null) {
            return new RefundDecided('expired', $event->amount, 'No response within the approval window.');
        }

        $approved = ($payload['decision'] ?? 'reject') === 'approve';

        return new RefundDecided(
            resolution: $approved ? 'approved' : 'rejected',
            amount: (float) ($payload['amount'] ?? $event->amount),
            feedback: isset($payload['feedback']) ? (string) $payload['feedback'] : null,
        );
    }
}
