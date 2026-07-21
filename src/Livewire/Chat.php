<?php

declare(strict_types=1);

namespace SolutionForest\AiKit\Ui\Livewire;

use Illuminate\Support\Str;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Livewire\Component;
use SolutionForest\AiKit\Models\Collection;
use SolutionForest\AiKit\Services\ChatService;
use Throwable;

/**
 * The ai-kit chat component.
 *
 *   <livewire:ai-kit.chat collection="handbook" />
 *
 * Renders a conversation, sends prompts to the configured RAG agent, and shows
 * source citations. Streams token-by-token over SSE when enabled (see the
 * accompanying JS + StreamController), with a non-streamed fallback that runs
 * entirely inside Livewire.
 */
final class Chat extends Component
{
    public ?string $collection = null;

    /** Optional agent class-string override (must extend RagAgent). */
    public ?string $agent = null;

    public string $placeholder = 'Ask a question…';

    public string $draft = '';

    /** @var array<int, array{id:string, role:string, content:string, citations:array<int, array<string, mixed>>, pending:bool}> */
    public array $messages = [];

    public ?string $conversationId = null;

    public bool $streaming = true;

    /** Show a conversation-history sidebar (requires an authenticated user). */
    public bool $history = false;

    public function mount(
        ?string $collection = null,
        ?string $agent = null,
        ?string $placeholder = null,
        bool $history = false,
    ): void {
        $this->collection = $collection ?? config('ai-kit.default_collection', 'default');
        $this->agent = $agent;
        $this->placeholder = $placeholder ?? $this->placeholder;
        $this->streaming = (bool) config('ai-kit.chat.streaming', true);
        $this->history = $history;
    }

    /**
     * Past conversations for the authenticated user (newest first). Empty when
     * history is disabled or no user is signed in.
     *
     * @return array<int, array{id:string, title:string, when:string}>
     */
    public function conversationList(): array
    {
        $user = auth()->user();

        if (! $this->history || $user === null) {
            return [];
        }

        return Conversation::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->latest('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'updated_at'])
            ->map(fn (Conversation $c): array => [
                'id' => (string) $c->id,
                'title' => (string) ($c->title ?: 'Untitled chat'),
                'when' => (string) optional($c->updated_at)->diffForHumans(),
            ])
            ->all();
    }

    /**
     * Load a past conversation's messages back into the chat. Scoped to the
     * current user so one participant can't read another's history.
     */
    public function loadConversation(string $id): void
    {
        $user = auth()->user();

        if (! $this->history || $user === null) {
            return;
        }

        $owns = Conversation::query()
            ->whereKey($id)
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();

        if (! $owns) {
            return;
        }

        $this->messages = ConversationMessage::query()
            ->where('conversation_id', $id)
            ->orderBy('created_at')
            ->get(['role', 'content'])
            ->filter(fn (ConversationMessage $m): bool => in_array($m->role, ['user', 'assistant'], true)
                && trim((string) $m->content) !== '')
            ->map(fn (ConversationMessage $m): array => $this->message($m->role, (string) $m->content))
            ->values()
            ->all();

        $this->conversationId = $id;
    }

    /**
     * Start a fresh conversation.
     */
    public function newConversation(): void
    {
        $this->messages = [];
        $this->conversationId = null;
        $this->draft = '';
    }

    public function send(): void
    {
        $prompt = trim($this->draft);

        if ($prompt === '') {
            return;
        }

        $this->draft = '';

        $this->messages[] = $this->message('user', $prompt);

        // Citations are resolved from the retriever for the user's question so
        // they render in both tool and inject modes.
        $citations = $this->citationsFor($prompt);

        $assistantIndex = count($this->messages);
        $this->messages[] = $this->message('assistant', '', $citations, pending: true);

        if ($this->streaming) {
            // Hand off to the browser: JS opens an EventSource to the stream
            // route and calls completeStream() when done.
            $this->dispatch('ai-kit:stream', index: $assistantIndex, prompt: $prompt, collection: $this->collection);

            return;
        }

        $this->messages[$assistantIndex]['content'] = $this->answer($prompt);
        $this->messages[$assistantIndex]['pending'] = false;
    }

    /**
     * Called by the browser after a streamed response finishes so the final
     * text is persisted in component state.
     */
    public function completeStream(int $index, string $content, ?string $conversationId = null): void
    {
        if (isset($this->messages[$index])) {
            $this->messages[$index]['content'] = $content;
            $this->messages[$index]['pending'] = false;
        }

        if ($conversationId !== null) {
            $this->conversationId = $conversationId;
        }
    }

    /**
     * Non-streamed answer via the shared ChatService (RAG mode, conversation
     * persistence, and usage all handled there).
     */
    private function answer(string $prompt): string
    {
        try {
            $result = app(ChatService::class)->ask(
                $prompt,
                $this->collection,
                $this->conversationId,
                auth()->user(),
                $this->agent,
            );

            $this->conversationId = $result->conversationId ?? $this->conversationId;

            return $result->answer;
        } catch (Throwable $e) {
            report($e);

            return 'Sorry — something went wrong generating a response. Please try again.';
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function citationsFor(string $prompt): array
    {
        try {
            return app(ChatService::class)->citationsFor($prompt, $this->collection);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * True when the selected collection is missing or has no embedded content —
     * used to show a "create a knowledge base first" empty state.
     */
    public function knowledgeBaseEmpty(): bool
    {
        $slug = $this->collection ?? config('ai-kit.default_collection', 'default');

        $collection = Collection::query()->where('slug', $slug)->first();

        if ($collection === null) {
            return true;
        }

        return ! $collection->chunks()->whereNotNull('embedding')->exists();
    }

    /**
     * @param  array<int, array<string, mixed>>  $citations
     * @return array<string, mixed>
     */
    private function message(string $role, string $content, array $citations = [], bool $pending = false): array
    {
        return [
            'id' => (string) Str::uuid(),
            'role' => $role,
            'content' => $content,
            'citations' => $citations,
            'pending' => $pending,
        ];
    }

    public function render()
    {
        return view('ai-kit-ui::livewire.chat');
    }
}
