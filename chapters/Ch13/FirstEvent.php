<?php

declare(strict_types=1);

namespace NeuronBook\Ch13;

use NeuronAI\Workflow\Events\Event;

/**
 * Chapter 13 - events are the edges of the graph.
 *
 * Two corrections against the book's first-edition listing:
 *
 * 1. Event lives in NeuronAI\Workflow\Events, not NeuronAI\Workflow.
 *    So do StartEvent and StopEvent.
 *
 * 2. The property is `public readonly`, not `protected`. A node reads the
 *    event it was handed - `$event->firstMsg` - from a *different* class, so a
 *    promoted `protected` property is a fatal "Cannot access protected
 *    property" the moment the workflow runs. Events are messages between
 *    nodes; the payload has to be readable by the recipient.
 */
class FirstEvent implements Event
{
    public function __construct(public readonly string $firstMsg)
    {
    }
}
