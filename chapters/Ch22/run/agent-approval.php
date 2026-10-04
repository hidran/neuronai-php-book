<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Agent\Agent;
use NeuronAI\Chat\History\FileMessageStore;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\InputTranslationException;
use NeuronAI\Exceptions\RunInFlightException;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
use NeuronAI\Workflow\Persistence\FilePersistence;
use NeuronBook\Ch22\IssueRefundTool;

/*
 * Section 22.5 - agent tool approval across requests.
 *
 *   php chapters/Ch22/run/agent-approval.php
 *
 * No model needed: the fake provider scripts the model asking for a refund,
 * then answering once the tool has run. Every "request" below builds a fresh
 * agent from the thread ID alone, with the same durable persistence and
 * message store - FilePersistence and FileMessageStore standing in for
 * EloquentPersistence(WorkflowStore::class) and EloquentMessageStore.
 */

$storage = \dirname(__DIR__, 3) . '/storage/workflows/ch22-agent';
@\mkdir($storage, 0o777, true);
foreach (\glob($storage . '/*') ?: [] as $file) {
    \unlink($file);
}

$threadId = 'conv:77';
$issued = new ArrayObject();
$provider = new FakeAIProvider(
    new ToolCallMessage(null, [ToolCall::make('issue_refund', 'call_refund_1', ['order_id' => 1042, 'amount' => 89.9])]),
    new AssistantMessage('Done: your refund of 89.90 is on its way.'),
);

$agentFor = static function (string $threadId) use ($storage, $issued, $provider): Agent {
    $agent = Agent::make()
        ->setThreadId($threadId)
        ->setPersistence(new FilePersistence($storage))
        ->setMessageStore(new FileMessageStore($storage));
    $agent->addTool(new IssueRefundTool($issued));
    $agent->setAiProvider($provider);

    return $agent;
};

echo "Request 1 - the customer asks\n";
$state = $agentFor($threadId)->chat(new UserMessage('Please refund order 1042, it arrived broken.'));
echo '  interrupted: ' . \var_export($state->isInterrupted(), true) . "\n";

echo "\nRequest 2 - the customer types again before anyone decided\n";
try {
    $agentFor($threadId)->chat(new UserMessage('Hello? Anyone there?'));
} catch (RunInFlightException $e) {
    echo "  refused with RunInFlightException -> HTTP 409\n";
}

echo "\nRequest 3 - the manager's page loads (a cold process)\n";
foreach ($agentFor($threadId)->pendingApprovals() as $action) {
    echo '  ' . \json_encode($action, \JSON_THROW_ON_ERROR) . "\n";
}

echo "\nRequest 4 - a decision for a call the run is not waiting for\n";
try {
    $agentFor($threadId)->submitApprovalDecisions(['call_forged' => 'approve'])->run();
} catch (InputTranslationException $e) {
    echo "  refused: {$e->getMessage()} -> HTTP 422\n";
}

echo "\nRequest 5 - the manager approves\n";
$state = $agentFor($threadId)->submitApprovalDecisions(['call_refund_1' => 'approve'])->run();
echo '  interrupted: ' . \var_export($state->isInterrupted(), true) . "\n";
echo '  answer: ' . ($state->getMessage()?->getContent() ?? '') . "\n";
echo '  refunds issued: ' . \json_encode($issued->getArrayCopy(), \JSON_THROW_ON_ERROR) . "\n";
