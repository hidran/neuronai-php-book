<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Agent\Adapters\AgentChunkAdapter;
use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowState;
use NeuronBook\Ch21\EchoChannel;
use NeuronBook\Ch21\ProgressNode;

/*
 * Section 21.5 - push streaming through a channel.
 *
 *   php chapters/Ch21/run/channel.php
 *
 * No model needed. With both a stream adapter and a channel attached, run()
 * consumes the stream itself and delivers every event to the channel as it
 * is produced - which is what a queue worker does. Each line printed is one
 * envelope, exactly as PusherChannel or RedisChannel would publish it: a
 * segment streamId, a sequence number, the event type and its data. The
 * 300-byte budget forces the long final event into stream.fragment pieces
 * that share one sequence number; @neuron-core/streaming reassembles them.
 */

$state = Workflow::make(
    workflowId: 'content:42',
    state: new WorkflowState(['topic' => 'queues']),
)
    ->addNodes([new ProgressNode()])
    ->setStreamAdapter(new AgentChunkAdapter())
    ->setChannel(new EchoChannel(maxBytes: 300))
    ->run();

echo "\nrun() returned: " . $state->getStatus()->value . ', '
    . \strlen((string) $state->get('final')) . " characters in 'final'\n";
