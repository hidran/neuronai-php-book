<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
use NeuronBook\Ch10\RecordingWeatherTool;

/*
 * Lab 7 - the fake provider. No model, no network, no API key.
 *
 *   php chapters/Ch10/run/fake-provider.php
 *
 * The fake scripts the model's side of the conversation: first a tool call,
 * then a final answer. What is under test is everything in between - that
 * the agent resolved the call against its own tool, invoked it with the
 * scripted arguments, and fed the result back for a second inference.
 */

$provider = new FakeAIProvider(
    new ToolCallMessage(null, [
        ToolCall::make('get_current_weather', 'call_1', ['latitude' => 45.07, 'longitude' => 7.69]),
    ]),
    new AssistantMessage('It is 14 degrees in Turin.'),
);

/** @var ArrayObject<int, array{float, float}> $calls */
$calls = new ArrayObject();

$state = Agent::make()
    ->setAiProvider($provider)
    ->addTool(new RecordingWeatherTool($calls))
    ->chat(new UserMessage('What is the weather in Turin?'));

$provider->assertCallCount(2);
$provider->assertToolsConfigured(['get_current_weather']);

if ($calls->getArrayCopy() !== [[45.07, 7.69]]) {
    fwrite(STDERR, "The tool was not invoked with the scripted arguments.\n");
    exit(1);
}

echo $state->getMessage()?->getContent() . PHP_EOL;
echo "Tool invoked with: " . json_encode($calls->getArrayCopy()) . PHP_EOL;
