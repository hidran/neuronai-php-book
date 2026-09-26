<?php

declare(strict_types=1);

namespace NeuronBook\Ch22;

use NeuronAI\Workflow\Events\Event;

class RefundDecided implements Event
{
    public function __construct(
        public readonly string $resolution,
        public readonly float $amount,
        public readonly ?string $feedback = null,
    ) {
    }
}
