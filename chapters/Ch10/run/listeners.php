<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Observability\Events\ToolCalled;
use NeuronAI\Observability\LogListener;
use NeuronAI\Observability\ObservabilityEvent;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
use NeuronBook\Ch10\RecordingWeatherTool;
use Psr\Log\AbstractLogger;

/*
 * Section 10.2 - observability is a PSR-14 listener on the agent instance.
 *
 *   php chapters/Ch10/run/listeners.php
 *
 * Runs offline: the fake provider scripts one tool call and one answer, so
 * what you see is the event stream of a real agent loop with no model behind
 * it. LogListener prints every event by name; the closure subscribed to
 * ToolCalled shows that a listener can key on a single event class.
 *
 * The two ignores are for the framework, not for this code: in neuron-ai
 * 4.x-dev, WorkflowInterface::subscribe() documents its listener as
 * callable(object): void, which no typed listener - including the
 * framework's own LogListener - can satisfy at PHPStan level 8. The calls are
 * exactly the ones src/Observability/AGENTS.md prescribes, and they run.
 */

$logger = new class () extends AbstractLogger {
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        echo "[{$level}] {$message}" . PHP_EOL;
    }
};

$provider = new FakeAIProvider(
    new ToolCallMessage(null, [
        ToolCall::make('get_current_weather', 'call_1', ['latitude' => 45.07, 'longitude' => 7.69]),
    ]),
    new AssistantMessage('It is 14 degrees in Turin.'),
);

$agent = Agent::make()
    ->setAiProvider($provider)
    ->addTool(new RecordingWeatherTool(new ArrayObject()))
    ->subscribe(ObservabilityEvent::class, new LogListener($logger)) // @phpstan-ignore argument.type
    ->subscribe(ToolCalled::class, function (ToolCalled $event): void { // @phpstan-ignore argument.type
        echo '  -> ' . $event->tool->getName() . ' ' . json_encode($event->tool->getInputs()) . PHP_EOL;
    });

$agent->chat(new UserMessage('What is the weather in Turin?'));
