<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use NeuronAI\Chat\Messages\UserMessage;
use NeuronBook\Ch04\PersistentAgent;
use NeuronBook\Support\ProviderFactory;

/*
 * Lab 2 - a persistent CLI chat.
 *
 *   php chapters/Ch04/run/chat-loop.php project-alpha
 *
 * Quit, run it again with the same thread ID, and ask what you said.
 */

if (!ProviderFactory::isAvailable()) {
    exit("Provider not reachable. Start Ollama, or set a cloud key in .env.\n");
}

$threadId = $argv[1] ?? 'default';
$agent = PersistentAgent::make(threadId: $threadId);

echo "Thread: {$threadId} — /exit to quit, /reset to clear memory.\n\n";

while (true) {
    $input = \readline('> ');

    if ($input === false) {
        break;
    }

    $input = \trim($input);

    if ($input === '') {
        continue;
    }

    \readline_add_history($input);

    if ($input === '/exit') {
        break;
    }

    if ($input === '/reset') {
        $agent->resetConversation();
        echo "Memory cleared.\n\n";
        continue;
    }

    try {
        $reply = $agent->chat(new UserMessage($input))->getMessage();
        echo "\n" . $reply?->getContent() . "\n\n";
    } catch (\Throwable $e) {
        \fwrite(STDERR, "Error: {$e->getMessage()}\n\n");
    }
}
