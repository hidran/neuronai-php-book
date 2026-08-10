<?php

declare(strict_types=1);

namespace NeuronBook\Ch13;

use NeuronAI\Workflow\Events\Event;

class SecondEvent implements Event
{
    public function __construct(public readonly string $secondMsg)
    {
    }
}
