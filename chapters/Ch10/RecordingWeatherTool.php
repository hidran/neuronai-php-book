<?php

declare(strict_types=1);

namespace NeuronBook\Ch10;

use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

/**
 * Lab 7 - a stand-in for the Chapter 5 weather tool that records its calls
 * instead of making HTTP requests, so a test can assert on the arguments the
 * agent passed.
 *
 * The recording goes into an injected object, not into a property of the
 * tool: ToolNode executes a fresh clone of the registered tool for every
 * call, so anything written to the tool's own arrays is lost with the clone.
 * A shallow clone still shares the ArrayObject.
 */
class RecordingWeatherTool extends Tool
{
    protected string $name = 'get_current_weather';

    protected ?string $description = 'Returns the current temperature for a latitude/longitude pair.';

    /**
     * @param \ArrayObject<int, array{float, float}> $calls
     */
    public function __construct(private readonly \ArrayObject $calls)
    {
    }

    /**
     * @return ToolProperty[]
     */
    protected function properties(): array
    {
        return [
            new ToolProperty('latitude', PropertyType::NUMBER, 'Latitude in decimal degrees.', true),
            new ToolProperty('longitude', PropertyType::NUMBER, 'Longitude in decimal degrees.', true),
        ];
    }

    public function __invoke(float $latitude, float $longitude): string
    {
        $this->calls->append([$latitude, $longitude]);

        return '{"temperature_2m": 14.2}';
    }
}
