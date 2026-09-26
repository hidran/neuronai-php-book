<?php

declare(strict_types=1);

namespace NeuronBook\Ch14;

use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Node;

class WriteNode extends Node
{
    public function __invoke(StartEvent $event, ContentWorkflowState $state): DraftReady
    {
        $feedback = $state->lastFeedback();

        $draft = $feedback === null
            ? 'Draft v1'
            : \sprintf('Draft v%d (addressing: %s)', $state->revisionCount() + 1, $feedback);

        echo "- WriteNode produced: {$draft}\n";

        return new DraftReady($draft);
    }
}
