<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Chat\Messages\UserMessage;
use NeuronBook\Ch05\WeatherAgent;
use NeuronBook\Support\ProviderFactory;

/*
 * Section 5.7 - the capstone.
 *
 *   php chapters/Ch05/run/weather.php "What's the average temperature between Turin and Milan?"
 *
 * Needs a tool-calling model. llama3.2 and qwen work; very small models often
 * answer without calling the tool at all, which is itself worth seeing.
 */

if (!ProviderFactory::isAvailable()) {
    exit("Provider not reachable. Start Ollama, or set a cloud key in .env.\n");
}

$prompt = $argv[1] ?? "What's the average current temperature between Turin and Milan?";

$start = \microtime(true);

try {
    echo WeatherAgent::make()
        ->toolMaxRuns(6)
        ->chat(new UserMessage($prompt))
        ->getMessage()
        ->getContent() . PHP_EOL;

    \printf("\n[%.2fs]\n", \microtime(true) - $start);
} catch (\Throwable $e) {
    \fwrite(STDERR, "Failed: {$e->getMessage()}\n");
    exit(1);
}
