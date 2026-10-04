<?php

declare(strict_types=1);

namespace NeuronBook\Ch10;

use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Evaluation\Assertions\StringContainsAny;
use NeuronAI\Evaluation\BaseEvaluator;
use NeuronAI\Evaluation\Contracts\DatasetInterface;
use NeuronAI\Evaluation\Dataset\ArrayDataset;
use NeuronBook\Ch03\AssistantAgent;

/**
 * Chapter 10 - an evaluator is PHPUnit for a non-deterministic service.
 *
 * The four methods are the whole contract: getDataset() supplies the cases,
 * run() produces the output for one case, evaluate() asserts against it, and
 * setUp() prepares anything expensive once.
 *
 * Assertions score rather than compare. That is the point of Section 10.4:
 * equality is the wrong instrument for a service that is allowed to phrase
 * the same correct answer differently every time. The third argument to
 * assert() names the metric the score is recorded under (Section 10.5).
 *
 * The class is not declared final on purpose: EvaluatorDiscovery finds
 * evaluators with a /^class\s+/ regex, so a "final class" is silently skipped.
 *
 * Run it with:
 *   vendor/bin/neuron evaluation chapters/Ch10
 *
 * (evaluation.php loads .env, so --autoload-file is not needed.)
 *
 * A failure here is not necessarily a bug. Small local models get these
 * questions wrong now and then - llama3.2 has answered "final" to the readonly
 * question - and catching exactly that is what an evaluator is for. The runner
 * exits 1 when any item fails, which is what you want in CI.
 */
class AssistantEvaluator extends BaseEvaluator
{
    public function getDataset(): DatasetInterface
    {
        return new ArrayDataset([
            [
                'question' => 'What keyword makes a PHP property immutable after construction?',
                'expected_keywords' => ['readonly'],
            ],
            [
                'question' => 'Which PHP keyword prevents a class from being extended?',
                'expected_keywords' => ['final'],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $datasetItem
     */
    public function run(array $datasetItem): mixed
    {
        \assert(\is_string($datasetItem['question']));

        $state = AssistantAgent::make()->setThreadId('evaluator-' . \uniqid())->chat(new UserMessage($datasetItem['question']));

        return $state->getMessage()?->getContent() ?? '';
    }

    /**
     * @param array<string, mixed> $datasetItem
     */
    public function evaluate(mixed $output, array $datasetItem): void
    {
        /** @var string[] $keywords */
        $keywords = $datasetItem['expected_keywords'];

        $this->assert(new StringContainsAny($keywords), $output, 'keywords');
    }
}
