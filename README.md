# Agentic AI in PHP with NeuronAI — code

Runnable companion code for the book *Agentic AI in PHP with NeuronAI* by
Hidran Arias, published in English, Italian and Spanish.

Every example here has been executed or statically verified against the real
libraries. Nothing in this repository is a snippet that was never run.

| Verified against | Version |
|---|---|
| `neuron-core/neuron-ai` | 4.x (`df30064`, shortly before 4.0.0) |
| `neuron-core/neuron-laravel` | 2.x (`399936c`) |
| PHP | 8.2 – 8.5 |

`neuron-core/php-vector` has no v4-compatible release yet, so the PHPVector
listings in Chapter 12 are not part of the verified set.

---

## Getting started

```bash
git clone https://github.com/hidran/neuronai-php-book.git
cd neuronai-php-book
composer install
cp .env.example .env
```

**You do not need an API key.** The default configuration points at
[Ollama](https://ollama.com) running locally, which is free:

```bash
ollama pull llama3.2
ollama pull nomic-embed-text
```

Then run the first example:

```bash
php chapters/Ch03/run/chat.php "Explain readonly vs final in PHP 8"
```

To use a hosted provider instead, set `NEURON_PROVIDER` in `.env` to
`anthropic`, `openai`, `gemini` or `mistral` and fill in the matching key.
Every agent in the repository goes through `ProviderFactory`, so that one
variable switches all of them at once — which is the argument Chapter 3 makes
in prose, made mechanical here.

---

## Layout

```
chapters/
  Support/          ProviderFactory and Env, shared by every chapter
  Ch03/             classes for that chapter
  Ch03/run/         scripts you can execute
  ...
evaluation.php      output configuration for the eval runner (Chapter 10)
phpstan.neon        level 8, run over every example
```

Classes are autoloaded as `NeuronBook\ChNN\ClassName`. Scripts under `run/`
are executed directly and are not autoloaded.

---

## Chapter index

| Chapter | Directory | What runs |
|---|---|---|
| 3 — Setup and Your First Agent | [`Ch03`](chapters/Ch03) | `run/chat.php` — a full chat round-trip |
| 4 — Messages and Memory | [`Ch04`](chapters/Ch04) | `run/chat-loop.php` — a thread that survives a restart |
| 5 — Tools: Giving the Agent Hands | [`Ch05`](chapters/Ch05) | `run/weather.php` — real tool call to Open-Meteo; `run/server-load.php` — inline tool |
| 6 — Structured Output | [`Ch06`](chapters/Ch06) | `run/extract.php` — typed, validated DTO |
| 7 — Streaming | [`Ch07`](chapters/Ch07) | `run/stream.php`, `run/stream-tools.php`; `run/agui-endpoint.php` — an AG-UI SSE endpoint |
| 8 — Attachments and Multimodality | [`Ch08`](chapters/Ch08) | `run/extract-invoice.php` — PDF → typed `Invoice` |
| 9 — MCP | [`Ch09`](chapters/Ch09) | `run/mcp.php` — agent over a local MCP server |
| 10 — Observability, Evals and Testing | [`Ch10`](chapters/Ch10) | `vendor/bin/neuron evaluation chapters/Ch10`; `run/fake-provider.php`, `run/listeners.php` — no model needed |
| 12 — The NeuronAI RAG Pipeline | [`Ch12`](chapters/Ch12) | `run/ingest.php`, `run/ask.php` — local RAG; `run/isolation.php` — tenant-filtered search |
| 13 — The Event-Driven Model | [`Ch13`](chapters/Ch13) | `run/workflow.php` — three-node graph; `run/durable.php` — crash recovery with durable steps |
| 14 — Loops, Branches and State | [`Ch14`](chapters/Ch14) | `run/loop.php` — bounded loop, typed state |
| 15 — Human in the Loop | [`Ch15`](chapters/Ch15) | `run/start.php` + `run/resume.php` — pause in one process, resume in another |
| 21 — Streaming to the Frontend | [`Ch21`](chapters/Ch21) | `run/sse-frames.php`, `run/channel.php`, `run/disconnect.php` |
| 22 — Workflows and Human Approval in Production | [`Ch22`](chapters/Ch22) | `run/refund.php` — fenced resume, deadlines, retained completion; `run/agent-approval.php` |
| 23 — Production | [`Ch23`](chapters/Ch23) | `run/usage.php` — token usage from PSR-14 events |

Chapters 1, 2 and 11 are conceptual. The Laravel chapters 17 to 20 depend on
application models (`App\Models\*`), so their listings were verified with
PHPStan against stub classes rather than shipped here; chapters 21 to 23 ship
the framework-level parts that run without Laravel.

---

## Verifying it yourself

```bash
composer check      # php -l, then PHPStan level 8, then PHPUnit
composer smoke      # actually execute every runnable example
```

PHPStan runs against the real vendor tree, so an example that references a
class, method or named argument the library does not have fails the build.
That is deliberate: it is the mechanism that keeps this repository honest as
the framework moves.

`composer smoke` goes further and runs the examples for real:

```
No model required
-----------------
  Ch10 fake provider (Lab 7)         ok
  Ch10 event listeners               ok
  Ch13 event-driven workflow         ok
  Ch13 durable steps + memoize       ok
  Ch14 bounded loop + state          ok
  Ch21 SSE frames                    ok
  Ch21 streaming channel             ok
  Ch21 client disconnect             ok
  Ch22 refund workflow               ok
  Ch22 agent tool approval           ok
  Ch23 usage recorder                ok
  Ch15 interrupt                     ok
  Ch15 resume                        ok

Provider required
-----------------
  Ch03 chat                          ok
  Ch04 persistent chat loop          ok
  Ch05 tool call                     ok
  Ch05 inline tool                   ok
  Ch06 structured output             ok
  Ch07 streaming                     ok
  Ch07 streaming tool calls          ok
  Ch09 MCP over stdio                ok
  Ch12 RAG ingest                    ok
  Ch12 RAG query                     ok
  Ch12 tenant isolation              ok

passed 24, failed 0, skipped 0
```

The model-free examples run anywhere, including CI. The rest run whenever a
provider is reachable and are reported as skipped otherwise, so a missing key
never turns into a red build.

---

## What verification found

Verifying the book against v4 turned up defects in the printed text, in the
framework's documentation and in the framework itself. The book is corrected;
Appendix A lists the documentation drift. The ones worth knowing before you
write any code:

- **Binding is casting.** v4 converts tool inputs to the declared property
  type before `__invoke()` runs, so `"45.07"` arrives as `45.07` and a value
  that cannot be converted goes back to the model as an error. The v3 advice to
  widen `float` parameters to `float|int|string` is obsolete.
- **`required: true` does not validate.** It shapes the schema the model sees
  and is not checked on the way back. Pair every required scalar with a rule
  such as `#[NotBlank]`, or an omitted key becomes an uninitialised-property
  fatal instead of a retry. See [`Ch06/Person.php`](chapters/Ch06/Person.php).
- **A pause is a result.** `run()` returns an interrupted state; nothing is
  thrown. Resume with `resume($payload)->run()`, addressed by workflow ID.
- **`approvalPolicy()` takes no arguments**, whatever the docs show; the
  inputs are already bound on the tool.
- **`SQLChatHistory` and `EloquentChatHistory` now both take `threadId`**, the
  inconsistency the first edition tripped on.

Upstream defects worked around here, each with a comment pointing at it:

- `StdioTransport::connect()` escapes the arguments it appends but not the
  command itself, so any interpreter path containing a space is split by the
  shell and the MCP server dies instantly. That is the default on macOS with
  Laravel Herd. See [`Ch09/LocalToolsAgent.php`](chapters/Ch09/LocalToolsAgent.php).
- The comparison validation rules build retry messages without the field name
  and with the reference's type instead of its value.
- The stale-attempt fence on `resume()` throws a plain `WorkflowException`,
  not `StaleWorkflowRunException`. `tests/ApiContractTest.php` pins this so
  the day it is fixed, the build says so.
- `subscribe()` types its listener as `callable(object): void`, so typed
  listeners need a PHPStan ignore. See [`Ch10/run/listeners.php`](chapters/Ch10/run/listeners.php).

Fixed in v4, and removed from this repository: the `FileVectorStore` crash on
a store that had never been written, and the `Action::feedback()` method that
erased the value it was supposed to return.

---

## Licence

MIT. Use the code in your own projects freely.
