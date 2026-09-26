<?php

declare(strict_types=1);

namespace NeuronBook\Ch22;

use ArrayObject;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

/**
 * Section 22.5 - a consequential tool.
 *
 * It declares its own risk: approvalPolicy() returning a string counts as
 * "requires approval", and the string is the reason the approver sees. The
 * refunds it issues are recorded in an injected ArrayObject, because ToolNode
 * executes a clone of the registered tool for every call.
 */
class IssueRefundTool extends Tool
{
    protected string $name = 'issue_refund';

    protected ?string $description = 'Issue a refund for an order. Use only after checking eligibility.';

    /**
     * @param ArrayObject<int, array{int, float}> $issued
     */
    public function __construct(private readonly ArrayObject $issued)
    {
    }

    /**
     * @return ToolProperty[]
     */
    protected function properties(): array
    {
        return [
            new ToolProperty('order_id', PropertyType::INTEGER, 'The order to refund.', true),
            new ToolProperty('amount', PropertyType::NUMBER, 'The amount to refund, in euros.', true),
        ];
    }

    protected function approvalPolicy(): bool|string
    {
        return 'Refunds move money: a human signs off on every one.';
    }

    public function __invoke(int $order_id, float $amount): string
    {
        $this->issued->append([$order_id, $amount]);

        return \sprintf('Refund of %.2f issued for order %d.', $amount, $order_id);
    }
}
