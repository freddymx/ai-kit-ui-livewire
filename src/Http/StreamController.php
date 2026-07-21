<?php

declare(strict_types=1);

namespace SolutionForest\AiKit\Ui\Http;

use Illuminate\Http\Request;
use Laravel\Ai\Streaming\Events\TextDelta;
use Symfony\Component\HttpFoundation\StreamedResponse;
use SolutionForest\AiKit\Services\ChatService;
use Throwable;

/**
 * Streams a grounded answer to the browser as Server-Sent Events. The Livewire
 * component hands off to this route via JS (EventSource) when streaming is
 * enabled; each token is emitted as a `data:` frame and a final `done` event
 * carries the full text + conversation id. All RAG logic lives in ChatService.
 */
final class StreamController
{
    public function __construct(private readonly ChatService $chat)
    {
    }

    public function __invoke(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:8000'],
            'collection' => ['nullable', 'string', 'max:255'],
            'agent' => ['nullable', 'string'],
            'conversationId' => ['nullable', 'string', 'max:255'],
        ]);

        $prompt = $validated['prompt'];
        $collection = $validated['collection'] ?? config('ai-kit.default_collection', 'default');
        $agentClass = $validated['agent'] ?? null;
        $conversationId = $validated['conversationId'] ?? null;
        $user = $request->user();

        return response()->stream(function () use ($prompt, $collection, $conversationId, $user, $agentClass): void {
            $full = '';
            $finalConversationId = null;

            try {
                $stream = $this->chat->stream($prompt, $collection, $conversationId, $user, $agentClass);

                foreach ($stream as $event) {
                    if ($event instanceof TextDelta) {
                        $full .= $event->delta;
                        $this->emit('delta', ['delta' => $event->delta]);
                    }
                }

                $finalConversationId = $stream->conversationId ?? null;
            } catch (Throwable $e) {
                report($e);
                $this->emit('error', ['message' => 'Generation failed.']);
            }

            $this->emit('done', ['content' => $full, 'conversationId' => $finalConversationId]);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function emit(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_THROW_ON_ERROR) . "\n\n";

        if (function_exists('ob_flush')) {
            @ob_flush();
        }
        flush();
    }
}
