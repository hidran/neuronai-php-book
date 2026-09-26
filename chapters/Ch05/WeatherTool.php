<?php

declare(strict_types=1);

namespace NeuronBook\Ch05;

use NeuronAI\Exceptions\HttpException;
use NeuronAI\HttpClient\Curl\CurlHttpClient;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\HttpClient\HttpRequest;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolOutput;
use NeuronAI\Tools\ToolProperty;
use Uri\WhatWg\Url;

/**
 * Lab 3 - the capstone tool.
 *
 * Open-Meteo needs no API key, so this example genuinely runs. Note how much
 * work the description does: the model has nothing else to go on when it
 * decides whether to call this, and it has to derive the coordinates itself.
 *
 * The bare `float` parameters are safe: Tool::setInputs() casts every input
 * to its property's type before __invoke() runs, so "45.07" arrives as 45.07
 * and a value that cannot be converted never reaches this method at all.
 */
class WeatherTool extends Tool
{
    private const BASE_URL = 'https://api.open-meteo.com/v1/';

    protected string $name = 'get_current_weather';

    protected ?string $description = 'Returns current weather conditions for a geographic location: temperature '
        . 'in Celsius, wind speed in km/h, and a numeric weather code. Use this '
        . 'whenever the user asks about current weather, temperature, or conditions '
        . 'anywhere in the world. You must derive latitude and longitude yourself '
        . 'from the place name. Never invent weather data — always call this tool.';

    protected HttpClientInterface $client;

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

    public function __invoke(float $latitude, float $longitude): string|ToolOutput
    {
        // PHP 8.5: the pipe operator reads top to bottom - the parameters
        // become a query string, and the query string becomes the request.
        $request = [
            'latitude'  => $latitude,
            'longitude' => $longitude,
            'current'   => 'temperature_2m,wind_speed_10m,weather_code',
        ]
            |> \http_build_query(...)
            |> (static fn (string $query): HttpRequest => HttpRequest::get("forecast?{$query}"));

        try {
            $data = $this->getClient()->request($request)->json();
        } catch (HttpException) {
            return ToolOutput::error(
                'The weather service is unreachable right now. Do not retry; '
                . 'tell the user the data is unavailable.'
            );
        }

        if (!isset($data['current'])) {
            return ToolOutput::error('The weather service returned no current conditions for these coordinates.');
        }

        return \json_encode($data['current'], \JSON_THROW_ON_ERROR);
    }

    protected function getClient(): HttpClientInterface
    {
        // PHP 8.5: Uri\WhatWg\Url parses the endpoint the way a browser would,
        // so a malformed base URL fails here rather than on the first request.
        return $this->client ??= (new CurlHttpClient(timeout: 10.0))
            ->withBaseUri(new Url(self::BASE_URL)->toAsciiString());
    }
}
