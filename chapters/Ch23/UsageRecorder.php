<?php

declare(strict_types=1);

namespace NeuronBook\Ch23;

use Closure;
use NeuronAI\Agent\Observability\InferenceStop;

/**
 * Section 23.1 - one usage record per inference, from a PSR-14 listener.
 *
 * The book's version writes an Eloquent row; this one hands the record to a
 * closure so it runs without Laravel. The shape is the same: subscribe it to
 * InferenceStop and read the usage off the provider response, not off
 * $event->message - that is the last INPUT message.
 */
class UsageRecorder
{
    /**
     * @param Closure(array<string, int|string|null>): void $sink
     */
    public function __construct(
        private readonly string $agent,
        private readonly string $model,
        private readonly Closure $sink,
    ) {
    }

    public function __invoke(InferenceStop $event): void
    {
        $usage = $event->response->message()->getUsage();

        if ($usage === null) {
            return;   // the provider reported none
        }

        ($this->sink)([
            'agent'         => $this->agent,
            'model'         => $this->model,
            'input_tokens'  => $usage->inputTokens,
            'output_tokens' => $usage->outputTokens,
            'cached_tokens' => $usage->cachedInputTokens,
        ]);
    }
}
