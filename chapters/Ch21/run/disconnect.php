<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\RunInFlightException;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Workflow\Persistence\InMemoryPersistence;
use NeuronBook\Ch21\ClientDisconnected;

/*
 * Section 21.6 - stopping a stream when the browser disconnects.
 *
 *   php chapters/Ch21/run/disconnect.php
 *
 * No model needed. Two agent instances share one persistence object, as two
 * PHP requests share a database. The first streams one chunk and "loses" its
 * client; the second is the user's next message on the same thread.
 *
 * With a bare break, the abandoned run stays "running" under the Agent's
 * ten-minute lease and the next message is refused. Throwing into the
 * generator lets the run settle as failed, and the next message goes through.
 */

$scenario = static function (string $label, bool $throwIntoStream): void {
    $persistence = new InMemoryPersistence();
    $provider = (new FakeAIProvider(
        new AssistantMessage('A long answer nobody will read.'),
        new AssistantMessage('Here is the answer to your new question.'),
    ))->setStreamChunkSize(6);

    $stream = Agent::make(threadId: 'conv:42')
        ->setPersistence($persistence)
        ->setAiProvider($provider)
        ->stream(new UserMessage('First question'));

    \assert($stream instanceof Generator);

    foreach ($stream as $chunk) {
        // connection_aborted() would be true here in a real request.
        if ($throwIntoStream) {
            try {
                $stream->throw(new ClientDisconnected());
            } catch (ClientDisconnected) {
                // The run is now settled as failed; nothing else to do.
            }
        }
        break;
    }

    try {
        $state = Agent::make(threadId: 'conv:42')
            ->setPersistence($persistence)
            ->setAiProvider($provider)
            ->chat(new UserMessage('Second question'));

        echo "{$label}: next message answered - " . ($state->getMessage()?->getContent() ?? '') . "\n";
    } catch (RunInFlightException $e) {
        echo "{$label}: next message refused - RunInFlightException, lease until "
            . \date('H:i:s', (int) $e->leaseExpiresAt) . "\n";
    }
};

$scenario('break only        ', false);
$scenario('throw, then break ', true);
