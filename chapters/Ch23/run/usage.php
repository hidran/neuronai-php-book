<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Usage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Observability\Events\InferenceStop;
use NeuronAI\Observability\Events\WorkflowEnd;
use NeuronAI\Testing\FakeAIProvider;
use NeuronBook\Ch23\UsageRecorder;

/*
 * Section 23.1 - measure first.
 *
 *   php chapters/Ch23/run/usage.php
 *
 * No model needed: the fake provider's answers carry the token counts a real
 * provider would report. Observability in v4 is a PSR-14 dispatcher owned by
 * each agent instance - subscribe() a listener to an event class, and it sees
 * every run of that instance.
 */

$provider = new FakeAIProvider(
    (new AssistantMessage('Your order ships tomorrow.'))->setUsage(new Usage(812, 37)),
    (new AssistantMessage('Yes, the refund was issued.'))->setUsage(new Usage(1240, 29, cachedInputTokens: 800)),
);

$agent = Agent::make();
$agent->setAiProvider($provider);

$rows = [];

// subscribe() is documented with class-typed listeners, but its interface
// declares callable(object): PHPStan rejects the narrower parameter type.
// @phpstan-ignore argument.type
$agent->subscribe(InferenceStop::class, new UsageRecorder(
    agent: $agent::class,
    model: $agent->getProvider()->getModel(),
    sink: static function (array $row) use (&$rows): void {
        $rows[] = $row;
    },
));

// @phpstan-ignore argument.type
$agent->subscribe(WorkflowEnd::class, static function (WorkflowEnd $event): void {
    echo '  run ended: ' . $event->state->getStatus()->value . "\n";
});

$agent->chat(new UserMessage('Where is my order?'));
$agent->chat(new UserMessage('And the refund?'));

foreach ($rows as $row) {
    echo '  ' . \json_encode($row, \JSON_THROW_ON_ERROR) . "\n";
}

\printf(
    "Total: %d input, %d output tokens across %d inferences\n",
    \array_sum(\array_column($rows, 'input_tokens')),
    \array_sum(\array_column($rows, 'output_tokens')),
    \count($rows),
);
