<?php

declare(strict_types=1);

namespace NeuronBook\Ch14;

use NeuronAI\Workflow\Events\Event;

class DraftReady implements Event
{
    public function __construct(public readonly string $draft)
    {
    }
}
