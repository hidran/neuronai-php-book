# Agentic AI in PHP with NeuronAI — code

Runnable companion code for the book *Agentic AI in PHP with NeuronAI* by
Hidran Arias, published in English, Italian and Spanish.

Every example here has been executed or statically verified against the real
libraries. Nothing in this repository is a snippet that was never run.

| Verified against | Version |
|---|---|
| `neuron-core/neuron-ai` | 3.16.4 |
| `neuron-core/neuron-laravel` | 1.3.0 |
| `neuron-core/php-vector` | 1.1.0 |
| PHP | 8.2 – 8.5 |

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
| 5 — Tools: Giving the Agent Hands | [`Ch05`](chapters/Ch05) | `run/weather.php` — real tool call to Open-Meteo |
| 6 — Structured Output | [`Ch06`](chapters/Ch06) | `run/extract.php` — typed, validated DTO |
| 7 — Streaming | [`Ch07`](chapters/Ch07) | `run/stream.php` — token-by-token with timings |
| 8 — Attachments and Multimodality | [`Ch08`](chapters/Ch08) | `run/extract-invoice.php` — PDF → typed `Invoice` |
| 9 — MCP | [`Ch09`](chapters/Ch09) | `run/mcp.php` — agent over a local MCP server |
| 10 — Observability, Evals and Testing | [`Ch10`](chapters/Ch10) | `vendor/bin/neuron evaluation chapters/Ch10` |
| 12 — The NeuronAI RAG Pipeline | [`Ch12`](chapters/Ch12) | `run/ingest.php`, `run/ask.php` — local RAG |
| 13 — The Event-Driven Model | [`Ch13`](chapters/Ch13) | `run/workflow.php` — three-node graph |
| 14 — Loops, Branches and State | [`Ch14`](chapters/Ch14) | `run/loop.php` — bounded loop, typed state |
| 15 — Human in the Loop | [`Ch15`](chapters/Ch15) | `run/start.php` + `run/resume.php` — interrupt and resume |

Chapters 1, 2, 4 and 11 are conceptual and carry no standalone code; their
listings are excerpts of classes that appear in full in the chapters above.

---

## Verifying it yourself

```bash
composer check      # php -l, then PHPStan level 8, then PHPUnit
```

PHPStan runs against the real vendor tree, so an example that references a
class, method or named argument the library does not have fails the build.
That is deliberate: it is the mechanism that keeps this repository honest as
the framework moves.

---

## Corrections to the first edition

Verifying these examples turned up defects in the printed book. They are fixed
here, and each fix carries a comment explaining what was wrong. The
substantive ones:

- **`float` tool parameters throw a `TypeError`.** Models emit JSON numbers as
  strings, and the framework's `Tool.php` — the file that makes the call —
  declares `strict_types=1`. Since strict mode is decided by the *call site*,
  your own `declare()` is irrelevant. Widen to `float|int|string` and cast.
  See [`Ch05/WeatherTool.php`](chapters/Ch05/WeatherTool.php).
- **Content blocks take `content:`, not `source:`.** `FileContent`,
  `ImageContent`, `VideoContent` and `AudioContent` all name the first
  parameter `$content`.
- **`SourceType` is `NeuronAI\Chat\Enums\SourceType`** — a different namespace
  from the content blocks it is used with.
- **Workflow events live in `NeuronAI\Workflow\Events\`** — `StartEvent`,
  `StopEvent` and `Event` itself. `WorkflowInterrupt` is in
  `NeuronAI\Workflow\Interrupt\`.
- **Event payloads must be `public readonly`**, not `protected` — the node that
  receives the event is a different class.
- **The eval config is a list, not a class ⇒ options map**, and the classes are
  `ConsoleOutput` / `JsonOutput`. See [`evaluation.php`](evaluation.php).
- **The CLI command is `neuron evaluation`, singular.** This settles Appendix
  A, item 18, which the book leaves open.
- **`EloquentChatHistory` takes `threadId`**, while `SQLChatHistory` takes
  `thread_id`. The library is inconsistent; the book follows it only halfway.
- **`ToolProperty` has no `nullable` parameter.**

Two upstream bugs are worked around rather than fixed, with comments pointing
at them: `FileVectorStore` cannot re-index a store that does not exist yet, and
`Action::feedback()` erases the value it appears to read.

---

## Licence

MIT. Use the code in your own projects freely.
