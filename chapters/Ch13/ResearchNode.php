<?php

declare(strict_types=1);

namespace NeuronBook\Ch13;

use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

/**
 * Section 13.5 - a step that completes once.
 *
 * When this node returns, the engine commits its result as a durable step.
 * A later attempt at the same run replays that step from the store instead
 * of calling __invoke() again - so the echo below prints exactly once.
 */
class ResearchNode extends Node
{
    public function __invoke(StartEvent $event, WorkflowState $state): ResearchDone
    {
        echo "- ResearchNode: calling the slow research service\n";

        return new ResearchDone('Three sources, one counter-argument');
    }
}
