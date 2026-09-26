<?php

declare(strict_types=1);

namespace NeuronBook\Ch13;

use NeuronAI\Workflow\NodeInterface;
use NeuronAI\Workflow\Workflow;

/**
 * Section 13.5 - a workflow with a business identity.
 *
 * workflowId() declares the key this run is filed under in the store. Any
 * process that can build `new ReportWorkflow(42)` and reach the same
 * persistence can find and continue the run - no lookup table of run IDs.
 */
class ReportWorkflow extends Workflow
{
    public function __construct(private readonly int $reportId)
    {
        parent::__construct();
    }

    public function workflowId(): ?string
    {
        return 'report:' . $this->reportId;
    }

    /**
     * @return NodeInterface[]
     */
    protected function nodes(): array
    {
        return [
            new ResearchNode(),
            new PublishNode(),
        ];
    }
}
