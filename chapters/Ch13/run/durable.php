<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Workflow\Persistence\FilePersistence;
use NeuronBook\Ch13\ReportWorkflow;

/*
 * Section 13.5 - durable steps and memoize().
 *
 *   php chapters/Ch13/run/durable.php
 *
 * The first attempt fails inside PublishNode. The second attempt is a brand
 * new ReportWorkflow instance that shares nothing with the first except the
 * persistence directory and the workflow ID it declares. A plain run() finds
 * the failed run under that ID and recovers it: ResearchNode is replayed from
 * the store, the memoized draft is reused, and only the publish call runs again.
 */

$storage = \sys_get_temp_dir() . '/neuron-book-ch13';
$persistence = new FilePersistence($storage);

echo "Attempt 1\n";
try {
    ReportWorkflow::make(reportId: 42)->setPersistence($persistence)->run();
} catch (RuntimeException $e) {
    echo "  caught: {$e->getMessage()}\n";
}

echo "\nAttempt 2\n";
$state = ReportWorkflow::make(reportId: 42)->setPersistence($persistence)->run();

echo "\nWorkflow ID: {$state->getWorkflowId()}\n";
echo 'Published: ' . \var_export($state->get('published'), true) . "\n";
echo 'Status: ' . $state->getStatus()->name . "\n";
