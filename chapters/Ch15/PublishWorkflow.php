<?php

declare(strict_types=1);

namespace NeuronBook\Ch15;

use NeuronAI\Workflow\Workflow;

/**
 * A Workflow subclass declares its graph in nodes() instead of taking it from
 * addNodes(). That matters for interrupt/resume: the resumed process has to
 * rebuild the identical graph, and a class is how you guarantee that.
 */
class PublishWorkflow extends Workflow
{
    /**
     * @return array<int, \NeuronAI\Workflow\NodeInterface>
     */
    protected function nodes(): array
    {
        return [
            new ApprovalNode(),
        ];
    }
}
