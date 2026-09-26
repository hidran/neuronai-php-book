<?php

declare(strict_types=1);

namespace NeuronBook\Ch21;

use NeuronAI\Workflow\Streaming\Channel\AbstractChannel;

/**
 * Section 21.5 - the smallest possible transport.
 *
 * AbstractChannel owns the wire contract every built-in push channel shares:
 * the {streamId, sequence, type, data} envelope, fragmentation of oversized
 * events, batching and the lifecycle events. A transport implements only
 * deliver(). This one prints each batch, so you can read exactly what
 * PusherChannel or RedisChannel would publish.
 */
class EchoChannel extends AbstractChannel
{
    public function __construct(private readonly ?int $maxBytes = null)
    {
    }

    protected function budget(): ?int
    {
        return $this->maxBytes;
    }

    protected function deliver(string $batch): void
    {
        echo '  ' . $batch . "\n";
    }
}
