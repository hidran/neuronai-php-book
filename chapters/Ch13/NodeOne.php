<?php

declare(strict_types=1);

namespace NeuronBook\Ch13;

use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class NodeOne extends Node
{
    public function __invoke(FirstEvent $event, WorkflowState $state): SecondEvent
    {
        echo '- ' . $event->firstMsg . "\n";

        return new SecondEvent('NodeOne complete');
    }
}
