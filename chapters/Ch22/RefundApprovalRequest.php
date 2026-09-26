<?php

declare(strict_types=1);

namespace NeuronBook\Ch22;

use DateTimeImmutable;
use NeuronAI\Workflow\Interrupt\WaitForEventRequest;

/**
 * Section 22.4 - a versioned interrupt request.
 *
 * The request is serialised into the run's control record while the run is
 * suspended, so it outlives deployments. Two rules keep an old serialised
 * request loadable by new code:
 *
 * - a property added later is DECLARED with a default. Unserialising an old
 *   request leaves a promoted or default-less typed property uninitialised,
 *   and the first read of it is a fatal error;
 * - the wire shape (metadata()) carries a version, so the UI and the resume
 *   payload can tell which form they are dealing with.
 */
class RefundApprovalRequest extends WaitForEventRequest
{
    public const EVENT = 'refund.decided';

    public const VERSION = 2;

    // Added in version 2. Declared with a default, so requests suspended
    // by version 1 unserialise with 'EUR' instead of an uninitialised property.
    protected string $currency = 'EUR';

    public function __construct(
        protected string $message,
        protected int $orderId,
        protected float $amount,
        ?DateTimeImmutable $expiresAt = null,
    ) {
        parent::__construct(self::EVENT, $expiresAt);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    protected function metadata(): array
    {
        return [
            'version'  => self::VERSION,
            'message'  => $this->message,
            'orderId'  => $this->orderId,
            'amount'   => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
