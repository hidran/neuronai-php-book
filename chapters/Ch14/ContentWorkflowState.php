<?php

declare(strict_types=1);

namespace NeuronBook\Ch14;

use NeuronAI\Workflow\WorkflowState;

/**
 * Section 14.4 - a typed state object.
 *
 * WorkflowState's get()/set() is a bag of strings. A subclass turns the same
 * data into an API you can read: revisionCount() and hasReachedLimit() say
 * what the workflow is deciding, where $state->get('review_attempts', 0)
 * only says where the number is kept.
 *
 * The constraint from Section 14.5 still applies: state is serialised at an
 * interrupt, so it must hold no resources, connections or closures. IDs in,
 * rehydrate inside the node.
 *
 * @phpstan-type Revision array{draft: string, feedback: string}
 */
class ContentWorkflowState extends WorkflowState
{
    /** @var list<array{draft: string, feedback: string}> */
    protected array $revisions = [];

    public function addRevision(string $draft, string $feedback): self
    {
        $this->revisions[] = ['draft' => $draft, 'feedback' => $feedback];

        return $this;
    }

    public function revisionCount(): int
    {
        return \count($this->revisions);
    }

    public function hasReachedLimit(int $max = 3): bool
    {
        return $this->revisionCount() >= $max;
    }

    public function lastFeedback(): ?string
    {
        $last = \end($this->revisions);

        return $last === false ? null : $last['feedback'];
    }
}
