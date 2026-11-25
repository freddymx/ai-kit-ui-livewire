# ai-kit-ui-livewire

![PHP](https://img.shields.io/badge/PHP-8.5+-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12%20|%2013-FF2D20?logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-4-FB70A9?logo=livewire&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)

**The Livewire chat UI for the ai-kit suite** — a drop-in `<livewire:ai-kit.chat />` component with streaming RAG answers, markdown, source citations, and conversation history. **No build step required.**

> Guía de desarrollo (objetivos, stack, estructura, estado y relación con la suite): [DEVELOPMENT.md](DEVELOPMENT.md)

<p align="center">
  <img src="art/chat.png" alt="ai-kit chat with citations and history" width="820">
</p>

## Why this package?

You have a knowledge base indexed with [ai-kit-core](https://github.com/freddymx/ai-kit-core)
and want to let users chat with it — with streaming answers and clickable source
citations — without building a chat UI from scratch or setting up a JS build
pipeline. Drop in one Livewire tag and you're done. It talks to core's
`ChatService`, so the answers match your CLI, API, and MCP surfaces exactly.

## Requirements

- PHP **8.5+**, Laravel **12 or 13**
- **Livewire 4**

> **Depends on [`freddymx/ai-kit-core`](https://github.com/freddymx/ai-kit-core)** — the RAG engine (ingestion, retrieval, agents). It is a hard dependency and is installed automatically with this package; you configure your knowledge bases and AI provider there. This package is only the chat UI. Local models (Ollama, LM Studio, vLLM, llama.cpp) are configured in core via `AI_KIT_LOCAL_*` or from the **AI Settings** page of `ai-kit-filament`; the component picks up whichever provider is active automatically.

## Install

```bash
composer require freddymx/ai-kit-ui-livewire
```

Auto-discovered. Views are publishable if you want to customise them:

```bash
php artisan vendor:publish --tag=ai-kit-ui-views
```

## Migrating from `solution-forest/ai-kit-ui-livewire`

Up to 0.2 this package was `solution-forest/ai-kit-ui-livewire`; from 0.3 it is
`freddymx/ai-kit-ui-livewire`. The PHP namespace `SolutionForest\AiKit\` and the
`<livewire:ai-kit.chat />` tag are unchanged.

```bash
composer remove solution-forest/ai-kit-ui-livewire
composer require freddymx/ai-kit-ui-livewire:^0.4
```

Its dependency on core now points to `freddymx/ai-kit-core:^0.4`. The old
`solution-forest/*` packages remain on Packagist at v0.2.x.

## Usage

Drop it into any Blade view:

```blade
<livewire:ai-kit.chat collection="handbook" />
```

### Props

| Prop | Type | Default | Description |
|---|---|---|---|
| `collection` | `string` | config default | Knowledge-base slug to search |
| `agent` | `string` (class) | config agent | Agent class override (must extend `RagAgent`) |
| `placeholder` | `string` | "Ask a question…" | Input placeholder |
| `history` | `bool` | `config('ai-kit.ui.history')` (`false`) | Show the conversation-history sidebar (needs an authenticated user) |
| `citations` | `bool` | `config('ai-kit.ui.citations')` (`true`) | Show source citation chips + popups (set `false` to hide, and skip retrieval for them) |
| `streaming` | `bool` | `config('ai-kit.chat.streaming')` (`true`) | Stream the answer token-by-token over SSE (else non-streamed) |

Every boolean prop falls back to its config default when omitted, so you can set
an app-wide default in `config/ai-kit.php` and override per instance:

```blade
<livewire:ai-kit.chat collection="handbook" :citations="false" :streaming="false" />
```

```blade
<livewire:ai-kit.chat
    collection="handbook"
    :agent="\App\Ai\SupportAgent::class"
    :history="true"
    placeholder="Ask about the handbook…" />
```

## Features

- **Grounded header** — a double-bezel header shows the assistant identity, the active collection and a quiet "Live" status pill. Its **New chat** button resets the conversation even when the history sidebar is disabled.
- **Streaming** — token-by-token over SSE (route `ai-kit.stream`), controlled by `config('ai-kit.chat.streaming')`, with a non-streamed fallback that runs entirely inside Livewire.
- **Citations** — every answer shows source chips (document title · chunk · relevance %). Click a chip to expand the **source passage** inline with a relevance meter.
- **History** — with `:history="true"` and a signed-in user, a sidebar lists past conversations; click to reload, or start a **New chat**. Scoped to the current user.
- **Empty state** — when the collection is missing or has no embedded content, the component prompts you to create a knowledge base first.
- **Markdown** — assistant responses render GitHub-flavored markdown (HTML escaped).

<p align="center">
  <img src="art/citation-popup.png" alt="Citation popup showing the source passage" width="620">
</p>

## Standalone (non-Filament) page

The component needs Livewire assets + a CSRF meta tag. Minimal host page:

```blade
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
</head>
<body>
    <livewire:ai-kit.chat collection="handbook" :history="true" />
    @livewireScripts
</body>
</html>
```

> Conversation **history + token logging require an authenticated user** (the conversation participant). On a public page without auth, chat still works but nothing is persisted.

## Theming

The component ships compiled, scoped CSS using CSS variables — override them in your own stylesheet:

```css
.ck-root {
    --ck-accent: #16a34a;      /* buttons, links, active states */
    --ck-user: #16a34a;        /* user bubble */
    --ck-radius: 12px;
    /* --ck-bg, --ck-fg, --ck-muted, --ck-border, --ck-assistant, --ck-user-fg */
    /* header island */
    --ck-header-tint: #f6f8fc;      /* tray, eyebrow and status pill */
    --ck-header-text: #0b1220;     /* title */
    --ck-header-hair: rgba(11,18,32,.07);
    --ck-header-glow: rgba(37,99,235,.10);
    --ck-header-status: #10b981;   /* live dot */
}
```

Dark mode is handled automatically via `prefers-color-scheme` (it overrides the `--ck-header-*` variables too). The header collapses below `768px`: it hides the subtitle and the button label and keeps only the icon. No new component props were added — the header is always rendered.

## How streaming works

On send, the component dispatches a browser event; JS opens an `EventSource` to `ai-kit.stream` (`StreamController`), which streams the answer and emits `delta` frames, then a `done` frame carrying the final text + conversation id. The route middleware is configurable via `config('ai-kit.ui.stream_middleware')` (default `['web']`).

## Architecture

The component is a thin view over the core `ChatService`. Both the non-streamed
path and the SSE `StreamController` delegate to
`SolutionForest\AiKit\Services\ChatService`, so RAG mode (tool/inject),
conversation persistence, citations, and token usage are handled once in core
and shared with the CLI, HTTP API, and MCP server.

## The ai-kit suite

| Package | Purpose |
| --- | --- |
| [freddymx/ai-kit-core](https://github.com/freddymx/ai-kit-core) | Ingestion, retrieval, agents + CLI, HTTP API, MCP |
| **freddymx/ai-kit-ui-livewire** (this) | Livewire 4 chat component |
| [freddymx/ai-kit-filament](https://github.com/freddymx/ai-kit-filament) | Filament v5 admin panel |

Prefer a headless UI? The same chat is available as an HTTP API or MCP server in
`ai-kit-core` — no Livewire required.

## Upgrading from ai-kit 0.2

This package is part of the **0.3 breaking release** of the ai-kit suite: PHP
**8.5+**, Livewire **^4.4**, Pest **5**, `ai-kit-core` at `^0.3`, and `dev-main`
aliased to `0.3.x-dev`. Upgrade core (and Filament, if you use it) in lockstep.

The Laravel AI SDK v1 schema dropped the `user_id` column, so conversation
history is now scoped by `participant_type` / `participant_id`. On an existing
app, publish and run core's AI SDK backfill migration before deploying — see the
[core upgrade notes](https://github.com/freddymx/ai-kit-core#upgrading-from-ai-kit-02-ai-sdk-0x--1x).
Nothing else in this package's public API changed: the `<livewire:ai-kit.chat />`
tag, its props, streaming and citations behave the same.

## Continuous integration

`.github/workflows/tests.yml` runs the suite on a GitHub **release** (and manual
dispatch) — not on every push. `composer.json` declares a local **path**
repository at `../ai-kit-core` so cross-package changes can be tested together,
so CI clones that sibling first. The package itself is published on Packagist as
`freddymx/ai-kit-ui-livewire`; the clone step is only about the local path repo
and can be dropped together with it if you stop developing both side by side.

## Author

Created and maintained by **Alfredo Pineda** (<freddy@canariasnetworks.com>).
Originally published under the [SolutionForest](https://www.solutionforest.com)
organization; from 0.3 on, published in the `freddymx` Packagist namespace.

## License

MIT © [SolutionForest](https://www.solutionforest.com), Alfredo Pineda
