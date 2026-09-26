<?php

declare(strict_types=1);

namespace NeuronBook\Ch21;

use Generator;
use NeuronAI\Agent\Adapters\Events\ActivityStreamEvent;
use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

/**
 * Section 21.5 - a node that reports progress while it works.
 *
 * Yielded values are live output, not routing: the node still returns its
 * event. ActivityStreamEvent is protocol-neutral; the adapter decides what it
 * looks like on the wire. The last activity carries a long payload, to show a
 * channel with a small byte budget splitting it into fragments.
 */
class ProgressNode extends Node
{
    public function __invoke(StartEvent $event, WorkflowState $state): Generator
    {
        $topic = (string) $state->get('topic');

        foreach (['outline', 'draft'] as $step) {
            yield new ActivityStreamEvent(
                id: 'content-job',
                type: 'writing',
                data: ['step' => $step, 'topic' => $topic],
            );
        }

        $final = \str_repeat("A paragraph about {$topic}. ", 12);

        yield new ActivityStreamEvent(
            id: 'content-job',
            type: 'writing',
            data: ['step' => 'done', 'preview' => $final],
        );

        $state->set('final', $final);

        return new StopEvent();
    }
}
