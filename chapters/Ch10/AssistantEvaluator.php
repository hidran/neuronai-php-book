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
 * Assertions score rather than compare. That is the point of Section 10.3:
 * equality is the wrong instrument for a service that is allowed to phrase
 * the same correct answer differently every time.
 *
 * Run it with:
 *   vendor/bin/neuron evaluation chapters/Ch10 --autoload-file=bootstrap.php
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

        return AssistantAgent::make()
            ->chat(new UserMessage($datasetItem['question']))
            ->getMessage()
            ->getContent();
    }

    /**
     * @param array<string, mixed> $datasetItem
     */
    public function evaluate(mixed $output, array $datasetItem): void
    {
        /** @var string[] $keywords */
        $keywords = $datasetItem['expected_keywords'];

        $this->assert(new StringContainsAny($keywords), $output);
    }
}
