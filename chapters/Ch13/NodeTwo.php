<?php

declare(strict_types=1);

namespace NeuronBook\Ch13;

use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class NodeTwo extends Node
{
    public function __invoke(SecondEvent $event, WorkflowState $state): StopEvent
    {
        echo '- ' . $event->secondMsg . "\n";
        echo "- NodeTwo complete\n";

        $state->set('answer', 'Hello World!');

        return new StopEvent();
    }
}
