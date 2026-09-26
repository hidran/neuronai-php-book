#!/usr/bin/env bash
#
# Runs every executable example in the repository and reports pass/fail.
#
#   ./smoke.sh
#
# Examples that need no model run always. Examples that call a provider run
# only when one is reachable - so this is safe in CI, where it degrades to
# checking the model-free half rather than failing on a missing key.

set -uo pipefail
cd "$(dirname "$0")"

pass=0; fail=0; skip=0

run() {
    local label="$1"; shift
    printf '  %-34s ' "$label"
    if output=$("$@" 2>&1); then
        printf 'ok\n'; pass=$((pass + 1))
    else
        printf 'FAIL\n'
        printf '%s\n' "$output" | sed 's/^/      /' | head -12
        fail=$((fail + 1))
    fi
}

provider_up() {
    php -r '
        require "vendor/autoload.php";
        if (is_file(".env")) { Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad(); }
        exit(NeuronBook\Support\ProviderFactory::isAvailable() ? 0 : 1);
    ' >/dev/null 2>&1
}

# Serves the Section 7.5 AG-UI endpoint over real HTTP and requires a complete
# event stream: a run that errors mid-way still returns 200, so the status code
# proves nothing - RUN_FINISHED does.
agui_roundtrip() {
    local port=8799 out
    php -S "127.0.0.1:$port" chapters/Ch07/run/agui-endpoint.php >/dev/null 2>&1 &
    local pid=$!
    sleep 1
    out=$(curl -sN -m 120 "127.0.0.1:$port" \
        -d '{"threadId":"smoke","runId":"r1","messages":[{"id":"m1","role":"user","content":"Reply with exactly: OK"}]}')
    kill "$pid" 2>/dev/null
    printf '%s\n' "$out" | grep -q '"type":"RUN_FINISHED"' || { printf '%s\n' "$out"; return 1; }
}

echo
echo "No model required"
echo "-----------------"
run "Ch10 fake provider (Lab 7)"   php chapters/Ch10/run/fake-provider.php
run "Ch10 event listeners"         php chapters/Ch10/run/listeners.php
run "Ch13 event-driven workflow"   php chapters/Ch13/run/workflow.php
run "Ch13 durable steps + memoize"  php chapters/Ch13/run/durable.php
run "Ch14 bounded loop + state"    php chapters/Ch14/run/loop.php

run "Ch21 SSE frames"              php chapters/Ch21/run/sse-frames.php
run "Ch21 streaming channel"       php chapters/Ch21/run/channel.php
run "Ch21 client disconnect"       php chapters/Ch21/run/disconnect.php
run "Ch22 refund workflow"         php chapters/Ch22/run/refund.php
run "Ch22 agent tool approval"     php chapters/Ch22/run/agent-approval.php
run "Ch23 usage recorder"          php chapters/Ch23/run/usage.php
rm -rf storage/workflows
run "Ch15 interrupt"               php chapters/Ch15/run/start.php
if id=$(ls storage/workflows/pending-*.json 2>/dev/null | head -1); then
    id=$(basename "$id" .json); id=${id#pending-}
    run "Ch15 resume"              php chapters/Ch15/run/resume.php "$id" approve
fi

echo
echo "Provider required"
echo "-----------------"
if provider_up; then
    run "Ch03 chat"                php chapters/Ch03/run/chat.php "Reply with exactly: OK"
    run "Ch04 persistent chat loop"  sh -c 'printf "Reply with exactly: OK\n/exit\n" | php chapters/Ch04/run/chat-loop.php smoke; rm -f storage/chat/neuron_smoke.chat'
    run "Ch05 tool call"           php chapters/Ch05/run/weather.php "Temperature in Turin, Italy?"
    run "Ch05 inline tool"         php chapters/Ch05/run/server-load.php
    run "Ch06 structured output"   php chapters/Ch06/run/extract.php
    run "Ch07 streaming"           php chapters/Ch07/run/stream.php "Say only: ok"
    run "Ch07 AG-UI endpoint over HTTP"  agui_roundtrip
    run "Ch07 streaming tool calls"  php chapters/Ch07/run/stream-tools.php
    run "Ch09 MCP over stdio"      php chapters/Ch09/run/mcp.php "What PHP version is the server running?"
    run "Ch12 RAG ingest"          php chapters/Ch12/run/ingest.php
    run "Ch12 RAG query"           php chapters/Ch12/run/ask.php "Why re-index after changing the embeddings model?"
    run "Ch12 tenant isolation"    php chapters/Ch12/run/isolation.php
else
    echo "  no provider reachable - skipping the provider examples"
    echo "  (start Ollama, or set a key in .env)"
    # Count the provider examples above rather than hard-coding a number
    # that goes stale every time one is added.
    skip=$(awk '/^if provider_up; then/{f=1; next} /^else/{f=0} f && /^ *run /' "$0" | wc -l | tr -d ' ')
fi

echo
echo "passed $pass, failed $fail, skipped $skip"
exit $(( fail > 0 ? 1 : 0 ))
