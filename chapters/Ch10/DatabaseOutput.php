<?php

declare(strict_types=1);

namespace NeuronBook\Ch10;

use NeuronAI\Evaluation\Contracts\EvaluationOutputInterface;
use NeuronAI\Evaluation\Runner\EvaluationReport;

/**
 * Section 10.6 - a custom output driver that turns eval runs into a trend.
 *
 * A driver receives the EvaluationReport for the whole run: one
 * EvaluatorReport per discovered evaluator, plus the start and finish
 * instants. getResults() flattens every evaluator's items into a single
 * EvaluationResults, which is where the counts and the success rate live.
 *
 * It needs constructor arguments, so evaluation.php must hand it over as an
 * instance rather than a class-string (EvaluationOutputResolver does no
 * option mapping).
 */
class DatabaseOutput implements EvaluationOutputInterface
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly string $table = 'evaluations'
    ) {}

    public function output(EvaluationReport $report): void
    {
        $results = $report->getResults();

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (passed, failed, success_rate, total_time, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())"
        );

        $stmt->execute([
            $results->getPassedCount(),
            $results->getFailedCount(),
            $results->getSuccessRate(),
            $report->getDuration(),
        ]);
    }
}
