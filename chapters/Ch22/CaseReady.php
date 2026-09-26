<?php

declare(strict_types=1);

namespace NeuronBook\Ch22;

use NeuronAI\Workflow\Events\Event;

class CaseReady implements Event
{
    public function __construct(
        public readonly int $orderId,
        public readonly float $amount,
        public readonly string $summary,
    ) {
    }
}
