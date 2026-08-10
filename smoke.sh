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

echo
echo "No model required"
echo "-----------------"
run "Ch13 event-driven workflow"   php chapters/Ch13/run/workflow.php
run "Ch14 bounded loop + state"    php chapters/Ch14/run/loop.php

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
    run "Ch05 tool call"           php chapters/Ch05/run/weather.php "Temperature in Turin, Italy?"
    run "Ch06 structured output"   php chapters/Ch06/run/extract.php
    run "Ch07 streaming"           php chapters/Ch07/run/stream.php "Say only: ok"
    run "Ch09 MCP over stdio"      php chapters/Ch09/run/mcp.php "What PHP version is the server running?"
    run "Ch12 RAG ingest"          php chapters/Ch12/run/ingest.php
    run "Ch12 RAG query"           php chapters/Ch12/run/ask.php "Why re-index after changing the embeddings model?"
else
    echo "  no provider reachable - skipping 7 examples"
    echo "  (start Ollama, or set a key in .env)"
    skip=7
fi

echo
echo "passed $pass, failed $fail, skipped $skip"
exit $(( fail > 0 ? 1 : 0 ))
