<?php

declare(strict_types=1);

namespace NeuronBook\Ch13;

use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

/**
 * Section 13.2 - the type declarations *are* the graph.
 *
 * There is no wiring step and no edge list. This node consumes StartEvent and
 * produces FirstEvent, and that pair of type hints is the entire routing rule.
 */
class InitialNode extends Node
{
    public function __invoke(StartEvent $event, WorkflowState $state): FirstEvent
    {
        echo "- Handling StartEvent\n";

        return new FirstEvent('InitialNode complete');
    }
}
