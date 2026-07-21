<?php

declare(strict_types=1);

namespace SolutionForest\AiKit\Ui\Http;

use Illuminate\Http\Request;
use Laravel\Ai\Streaming\Events\TextDelta;
use Symfony\Component\HttpFoundation\StreamedResponse;
use SolutionForest\AiKit\Agents\RagAgent;
use SolutionForest\AiKit\AiKit;
use SolutionForest\AiKit\Pipeline\ContextInjector;
use Throwable;

/**
 * Streams a RAG agent's answer to the browser as Server-Sent Events. The
 * Livewire component hands off to this route via JS (EventSource) when
 * streaming is enabled; each token is emitted as a `data:` frame and a final
 * `done` event carries the full text + conversation id.
 */
final class StreamController
{
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
        $agent = $this->makeAgent($validated['agent'] ?? null, $collection);

        // Persist the exchange when a user is authenticated (Conversations log).
        if (($user = $request->user()) !== null) {
            $agent = ! empty($validated['conversationId'])
                ? $agent->continue($validated['conversationId'], $user)
                : $agent->forUser($user);
        }

        $input = $agent->mode() === 'inject'
            ? app(ContextInjector::class)->inject($prompt, $collection, $agent->tenantId())
            : $prompt;

        return response()->stream(function () use ($agent, $input): void {
            $full = '';
            $conversationId = null;

            try {
                $stream = $agent->stream($input);

                foreach ($stream as $event) {
                    if ($event instanceof TextDelta) {
                        $full .= $event->delta;
                        $this->emit('delta', ['delta' => $event->delta]);
                    }
                }

                $conversationId = $stream->conversationId ?? null;
            } catch (Throwable $e) {
                report($e);
                $this->emit('error', ['message' => 'Generation failed.']);
            }

            $this->emit('done', ['content' => $full, 'conversationId' => $conversationId]);
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

    private function makeAgent(?string $class, string $collection): RagAgent
    {
        $class ??= config('ai-kit.agent.class', RagAgent::class);
        $tenant = app(AiKit::class)->tenant();

        $agent = is_string($class) && class_exists($class) ? new $class($collection, $tenant) : null;

        return $agent instanceof RagAgent ? $agent : new RagAgent($collection, $tenant);
    }
}
