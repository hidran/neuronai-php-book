<?php

declare(strict_types=1);

namespace NeuronBook\Ch05;

use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use RuntimeException;

/**
 * Section 5.7 - the capstone tool.
 *
 * Open-Meteo needs no API key, so this example genuinely runs. Note how much
 * work the description does: the model has nothing else to go on when it
 * decides whether to call this, and it has to derive the coordinates itself.
 */
class WeatherTool extends Tool
{
    public function __construct()
    {
        parent::__construct(
            'get_current_weather',
            'Returns current weather conditions for a geographic location: temperature '
            . 'in Celsius, wind speed in km/h, and a numeric weather code. Use this '
            . 'whenever the user asks about current weather, temperature, or conditions '
            . 'anywhere in the world. You must derive latitude and longitude yourself '
            . 'from the place name. Never invent weather data - always call this tool.'
        );
    }

    /**
     * @return ToolProperty[]
     */
    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'latitude',
                type: PropertyType::NUMBER,
                description: 'Latitude in decimal degrees. Example: 45.0703 for Turin, Italy. '
                           . 'Negative values for the southern hemisphere.',
                required: true,
            ),
            new ToolProperty(
                name: 'longitude',
                type: PropertyType::NUMBER,
                description: 'Longitude in decimal degrees. Example: 7.6869 for Turin, Italy. '
                           . 'Negative values for the western hemisphere.',
                required: true,
            ),
        ];
    }

    /**
     * Accept string as well as float, deliberately.
     *
     * PropertyType::NUMBER describes the JSON Schema sent to the model, but the
     * model decides what it actually emits, and most of them emit numbers as
     * JSON strings at least some of the time. The framework passes the decoded
     * value through untouched, and Tool.php - the file that performs the call -
     * declares strict_types=1. Because strict_types is decided by the call site,
     * a bare `float $latitude` throws a TypeError no matter what this file
     * declares. Widen the type here and cast.
     */
    public function __invoke(float|int|string $latitude, float|int|string $longitude): string
    {
        $url = \sprintf(
            'https://api.open-meteo.com/v1/forecast?latitude=%F&longitude=%F&current_weather=true',
            (float) $latitude,
            (float) $longitude,
        );

        $context = \stream_context_create(['http' => ['timeout' => 10]]);
        $raw = @\file_get_contents($url, false, $context);

        if ($raw === false) {
            throw new RuntimeException('The weather service is unreachable.');
        }

        /** @var array{current_weather?: array{temperature: float, windspeed: float, weathercode: int}} $data */
        $data = \json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);

        if (!isset($data['current_weather'])) {
            throw new RuntimeException('The weather service returned no current conditions.');
        }

        $current = $data['current_weather'];

        return \sprintf(
            'temperature: %.1f C, wind speed: %.1f km/h, weather code: %d',
            $current['temperature'],
            $current['windspeed'],
            $current['weathercode'],
        );
    }
}
