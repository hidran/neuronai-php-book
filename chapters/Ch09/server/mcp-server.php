<?php

declare(strict_types=1);

/*
 * A minimal MCP server, speaking JSON-RPC 2.0 over stdio.
 *
 * Chapter 9 talks about consuming MCP servers rather than writing them, but an
 * example you cannot run is not much of an example. This is the smallest thing
 * that satisfies the handshake NeuronAI's McpConnector performs: initialize,
 * tools/list, tools/call.
 *
 * It is deliberately dependency-free - it is a fixture, not a framework.
 */

/** @return array<string, mixed> */
function tool_definitions(): array
{
    return [
        'get_php_version' => [
            'definition' => [
                'name' => 'get_php_version',
                'description' => 'Returns the PHP version running this MCP server.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => (object) [],
                    'required' => [],
                ],
            ],
            'handler' => static fn (array $args): string => 'PHP ' . PHP_VERSION,
        ],
        'get_disk_free' => [
            'definition' => [
                'name' => 'get_disk_free',
                'description' => 'Returns the free disk space, in gigabytes, for a given path.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'path' => [
                            'type' => 'string',
                            'description' => 'Absolute filesystem path. Defaults to "/".',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            'handler' => static function (array $args): string {
                $path = \is_string($args['path'] ?? null) ? $args['path'] : '/';
                $free = @\disk_free_space($path);

                if ($free === false) {
                    return "Cannot read disk usage for {$path}.";
                }

                return \sprintf('%.1f GB free on %s', $free / 1024 ** 3, $path);
            },
        ],
    ];
}

function respond(mixed $id, mixed $result): void
{
    echo \json_encode(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]), "\n";
    \flush();
}

function respondError(mixed $id, int $code, string $message): void
{
    echo \json_encode([
        'jsonrpc' => '2.0',
        'id' => $id,
        'error' => ['code' => $code, 'message' => $message],
    ]), "\n";
    \flush();
}

$tools = tool_definitions();
$stdin = \fopen('php://stdin', 'r');

if ($stdin === false) {
    exit(1);
}

while (($line = \fgets($stdin)) !== false) {
    $line = \trim($line);

    if ($line === '') {
        continue;
    }

    try {
        /** @var array<string, mixed> $request */
        $request = \json_decode($line, true, 512, \JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        respondError(null, -32700, 'Parse error');
        continue;
    }

    $id = $request['id'] ?? null;
    $method = \is_string($request['method'] ?? null) ? $request['method'] : '';

    switch ($method) {
        case 'initialize':
            respond($id, [
                'protocolVersion' => '2024-11-05',
                'capabilities' => ['tools' => (object) []],
                'serverInfo' => ['name' => 'neuronai-book-demo', 'version' => '1.0.0'],
            ]);
            break;

        case 'notifications/initialized':
            // A notification carries no id and expects no response.
            break;

        case 'tools/list':
            respond($id, [
                'tools' => \array_values(\array_map(
                    static fn (array $t): array => $t['definition'],
                    $tools,
                )),
            ]);
            break;

        case 'tools/call':
            /** @var array<string, mixed> $params */
            $params = \is_array($request['params'] ?? null) ? $request['params'] : [];
            $name = \is_string($params['name'] ?? null) ? $params['name'] : '';
            /** @var array<string, mixed> $args */
            $args = \is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

            if (!isset($tools[$name])) {
                respondError($id, -32602, "Unknown tool: {$name}");
                break;
            }

            /** @var callable(array<string, mixed>): string $handler */
            $handler = $tools[$name]['handler'];

            respond($id, [
                'content' => [['type' => 'text', 'text' => $handler($args)]],
                'isError' => false,
            ]);
            break;

        default:
            if ($id !== null) {
                respondError($id, -32601, "Method not found: {$method}");
            }
    }
}
