<?php

declare(strict_types=1);

namespace NeuronBook\Ch13;

use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;
use RuntimeException;

/**
 * Section 13.5 - memoize() inside a step.
 *
 * The draft is the expensive part (in a real workflow, an LLM call). Wrapping
 * it in memoize() persists its result the moment it completes, so when the
 * publish call below fails and the node runs again, the draft is read back
 * from the store rather than paid for twice.
 */
class PublishNode extends Node
{
    /**
     * Stands in for a flaky HTTP endpoint: the first call fails.
     *
     * PHP 8.5: asymmetric visibility on a static property - anyone may read
     * the counter, only this node may change it.
     */
    public private(set) static int $publishCalls = 0;

    public function __invoke(ResearchDone $event, WorkflowState $state): StopEvent
    {
        $draft = $this->memoize('draft', function () use ($event): string {
            echo "- PublishNode: drafting from '{$event->notes}'\n";

            return 'Report based on: ' . $event->notes;
        });

        if (++self::$publishCalls === 1) {
            echo "- PublishNode: publishing... failed\n";
            throw new RuntimeException('Publisher unavailable');
        }

        echo "- PublishNode: publishing... done\n";
        $state->set('published', $draft);

        return new StopEvent();
    }
}
