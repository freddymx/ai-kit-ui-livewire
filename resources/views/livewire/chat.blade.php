@php use SolutionForest\AiKit\Ui\Support\Markdown; @endphp

<div class="ck-root {{ $history ? 'ck-root-history' : '' }}" wire:key="ck-chat-root">
    @once
        <style>
            .ck-root{--ck-bg:#ffffff;--ck-fg:#111827;--ck-muted:#6b7280;--ck-border:#e5e7eb;--ck-user:#2563eb;--ck-user-fg:#ffffff;--ck-assistant:#f3f4f6;--ck-accent:#2563eb;--ck-radius:14px;display:flex;gap:12px;height:100%;min-height:420px;font-family:ui-sans-serif,system-ui,-apple-system,sans-serif;text-align:left!important}
            .ck-root *{box-sizing:border-box}
            .ck-history{width:240px;flex:0 0 240px;display:flex;flex-direction:column;gap:8px;background:var(--ck-bg);color:var(--ck-fg);border:1px solid var(--ck-border);border-radius:var(--ck-radius);padding:10px;overflow:hidden}
            .ck-history-new{border:1px solid var(--ck-accent);background:var(--ck-accent);color:#fff;border-radius:10px;padding:8px 10px;font:inherit;font-size:.85rem;font-weight:600;cursor:pointer}
            .ck-history-list{flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:4px}
            .ck-history-item{text-align:left;border:0;background:transparent;color:var(--ck-fg);border-radius:8px;padding:8px 9px;cursor:pointer;display:flex;flex-direction:column;gap:2px;width:100%}
            .ck-history-item:hover{background:var(--ck-assistant)}
            .ck-history-item.is-active{background:var(--ck-assistant);outline:1px solid var(--ck-border)}
            .ck-history-title{font-size:.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
            .ck-history-when{font-size:.68rem;color:var(--ck-muted)}
            .ck-history-empty{color:var(--ck-muted);font-size:.8rem;padding:8px}
            .ck-chat{flex:1;min-width:0;display:flex;flex-direction:column;height:100%;min-height:420px;background:var(--ck-bg);color:var(--ck-fg);border:1px solid var(--ck-border);border-radius:var(--ck-radius);overflow:hidden}
            .ck-messages{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;align-items:stretch;gap:14px}
            .ck-empty{margin:auto;color:var(--ck-muted);text-align:center;font-size:.95rem;max-width:28ch}
            .ck-msg{display:flex;flex-direction:column;gap:6px;max-width:82%}
            .ck-msg-user{align-self:flex-end;align-items:flex-end;margin-left:auto}
            .ck-msg-assistant{align-self:flex-start;align-items:flex-start;margin-right:auto}
            .ck-bubble{padding:10px 14px;border-radius:var(--ck-radius);line-height:1.55;font-size:.95rem;text-align:left!important;word-wrap:break-word;overflow-wrap:anywhere}
            .ck-msg-user .ck-bubble{background:var(--ck-user);color:var(--ck-user-fg);border-bottom-right-radius:4px}
            .ck-msg-assistant .ck-bubble{background:var(--ck-assistant);border-bottom-left-radius:4px}
            /* Markdown children need their own declaration: a host rule on
               p/li would beat the inherited text-align of .ck-bubble. */
            .ck-bubble p,.ck-bubble ul,.ck-bubble ol,.ck-bubble li,.ck-bubble h1,.ck-bubble h2,.ck-bubble h3,.ck-bubble h4,.ck-bubble h5,.ck-bubble h6,.ck-bubble blockquote,.ck-bubble table,.ck-bubble th,.ck-bubble td,.ck-bubble pre,.ck-bubble code{text-align:left!important}
            .ck-bubble p{margin:0 0 .5em}.ck-bubble p:last-child{margin-bottom:0}
            .ck-bubble pre{background:#0f172a;color:#e2e8f0;padding:10px;border-radius:8px;overflow-x:auto;font-size:.85em}
            .ck-bubble code{font-family:ui-monospace,monospace;font-size:.9em}
            .ck-bubble a{color:var(--ck-accent)}
            .ck-cites{display:flex;flex-direction:column;gap:6px;margin-top:2px}
            .ck-cite-chips{display:flex;flex-wrap:wrap;gap:6px}
            .ck-cite{display:inline-flex;align-items:center;gap:5px;font-size:.72rem;color:var(--ck-muted);background:var(--ck-bg);border:1px solid var(--ck-border);border-radius:999px;padding:2px 9px;cursor:pointer;transition:border-color .15s,color .15s}
            .ck-cite:hover{border-color:var(--ck-accent);color:var(--ck-fg)}
            .ck-cite b{color:var(--ck-fg);font-weight:600}
            .ck-cite-score{font-variant-numeric:tabular-nums;opacity:.7}
            .ck-cite-pop{background:var(--ck-bg);border:1px solid var(--ck-border);border-radius:12px;padding:12px 14px;max-height:220px;overflow-y:auto}
            .ck-cite-pop-head{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:.72rem;color:var(--ck-muted);margin-bottom:8px;border-bottom:1px solid var(--ck-border);padding-bottom:6px}
            .ck-cite-pop-head b{color:var(--ck-fg)}
            .ck-cite-pop-close{border:0;background:transparent;color:var(--ck-muted);font-size:1rem;line-height:1;cursor:pointer;padding:0 2px}
            .ck-cite-pop-body{font-size:.82rem;line-height:1.55;color:var(--ck-fg);white-space:pre-wrap;word-break:break-word}
            .ck-meter{height:4px;border-radius:2px;background:var(--ck-border);overflow:hidden;margin-top:8px}
            .ck-meter>span{display:block;height:100%;background:var(--ck-accent)}
            [x-cloak]{display:none!important}
            .ck-dots{display:inline-flex;gap:3px}.ck-dots span{width:6px;height:6px;border-radius:50%;background:var(--ck-muted);animation:ck-blink 1.2s infinite both}
            .ck-dots span:nth-child(2){animation-delay:.2s}.ck-dots span:nth-child(3){animation-delay:.4s}
            @keyframes ck-blink{0%,80%,100%{opacity:.2}40%{opacity:1}}
            .ck-form{display:flex;gap:8px;padding:14px;border-top:1px solid var(--ck-border);background:var(--ck-bg)}
            .ck-input{flex:1;resize:none;border:1px solid var(--ck-border);border-radius:10px;padding:10px 12px;font:inherit;font-size:.95rem;color:var(--ck-fg);background:var(--ck-bg);max-height:140px}
            .ck-input:focus{outline:2px solid var(--ck-accent);outline-offset:-1px}
            .ck-send{border:0;background:var(--ck-accent);color:#fff;border-radius:10px;padding:0 18px;font:inherit;font-weight:600;cursor:pointer}
            .ck-send:disabled{opacity:.5;cursor:not-allowed}
            @media (max-width:640px){.ck-root-history{flex-direction:column}.ck-history{width:auto;flex:0 0 auto;max-height:160px}}
            @media (prefers-color-scheme:dark){.ck-root{--ck-bg:#0b0f19;--ck-fg:#e5e7eb;--ck-muted:#9ca3af;--ck-border:#1f2937;--ck-assistant:#1f2937}}

        </style>
    @endonce

    @if ($history)
        <aside class="ck-history">
            <button type="button" class="ck-history-new" wire:click="newConversation">+ New chat</button>
            <div class="ck-history-list">
                @forelse ($this->conversationList() as $c)
                    <button type="button" wire:key="conv-{{ $c['id'] }}"
                            class="ck-history-item {{ $conversationId === $c['id'] ? 'is-active' : '' }}"
                            wire:click="loadConversation('{{ $c['id'] }}')">
                        <span class="ck-history-title">{{ $c['title'] }}</span>
                        <span class="ck-history-when">{{ $c['when'] }}</span>
                    </button>
                @empty
                    <p class="ck-history-empty">No conversations yet.</p>
                @endforelse
            </div>
        </aside>
    @endif

    <div class="ck-chat">
        <div class="ck-messages" x-data x-ref="messages"
             x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
             @ck-scroll.window="$nextTick(() => $refs.messages.scrollTop = $refs.messages.scrollHeight)">
            @forelse ($messages as $i => $message)
                <div class="ck-msg ck-msg-{{ $message['role'] }}" wire:key="msg-{{ $message['id'] }}">
                    <div class="ck-bubble" @if($message['role']==='assistant') data-ck-index="{{ $i }}" @endif>
                        @if ($message['role'] === 'assistant')
                            @if ($message['pending'] && $message['content'] === '')
                                <span class="ck-dots"><span></span><span></span><span></span></span>
                            @else
                                {!! Markdown::toHtml($message['content']) !!}
                            @endif
                        @else
                            {{ $message['content'] }}
                        @endif
                    </div>

                    @if ($message['role'] === 'assistant' && ! empty($message['citations']))
                        <div class="ck-cites" x-data="{ open: null }" @keydown.escape.window="open = null">
                            <div class="ck-cite-chips">
                                @foreach ($message['citations'] as $ci => $cite)
                                    @php $pct = (int) round(($cite['score'] ?? 0) * 100); @endphp
                                    <button type="button" class="ck-cite"
                                            title="Relevance {{ $pct }}% — click to view source"
                                            @click="open = (open === {{ $ci }} ? null : {{ $ci }})">
                                        <b>{{ $cite['document_title'] ?? 'Source' }}</b>
                                        <span>#{{ $cite['chunk_index'] ?? 0 }}</span>
                                        <span class="ck-cite-score">{{ $pct }}%</span>
                                    </button>
                                @endforeach
                            </div>

                            @foreach ($message['citations'] as $ci => $cite)
                                @php $pct = (int) round(($cite['score'] ?? 0) * 100); @endphp
                                <div class="ck-cite-pop" x-show="open === {{ $ci }}" x-cloak x-transition.opacity>
                                    <div class="ck-cite-pop-head">
                                        <span><b>{{ $cite['document_title'] ?? 'Source' }}</b> · chunk #{{ $cite['chunk_index'] ?? 0 }} · {{ $pct }}% match</span>
                                        <button type="button" class="ck-cite-pop-close" @click="open = null" aria-label="Close">&times;</button>
                                    </div>
                                    <div class="ck-cite-pop-body">{{ $cite['content'] ?? '(source text unavailable)' }}</div>
                                    <div class="ck-meter"><span style="width: {{ $pct }}%"></span></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                @if ($this->knowledgeBaseEmpty())
                    <div class="ck-empty">
                        <strong>No knowledge base yet.</strong><br>
                        Create a collection and add documents first — in the admin panel
                        (Collections → Documents), or run <code>php artisan ai-kit:demo</code>
                        to load the sample docs.
                    </div>
                @else
                    <p class="ck-empty">Ask anything about the knowledge base to get started.</p>
                @endif
            @endforelse
        </div>

        <form class="ck-form" wire:submit="send">
            <textarea class="ck-input" wire:model="draft" rows="1"
                      placeholder="{{ $placeholder }}"
                      x-on:keydown.enter.prevent="$event.shiftKey || $wire.send()"></textarea>
            <button type="submit" class="ck-send" wire:loading.attr="disabled">Send</button>
        </form>
    </div>

    @script
    <script>
        const base = @js(route('ai-kit.stream'));

        $wire.on('ai-kit:stream', (payload) => {
            const { index, prompt, collection } = Array.isArray(payload) ? payload[0] : payload;
            const params = new URLSearchParams({ prompt, collection: collection ?? '' });
            if ($wire.agent) params.set('agent', $wire.agent);
            if ($wire.conversationId) params.set('conversationId', $wire.conversationId);

            const source = new EventSource(`${base}?${params.toString()}`);
            let buffer = '';

            const target = () => $wire.$el.querySelector(`[data-ck-index="${index}"]`);

            source.addEventListener('delta', (e) => {
                buffer += JSON.parse(e.data).delta ?? '';
                const el = target();
                if (el) { el.textContent = buffer; }
                window.dispatchEvent(new CustomEvent('ck-scroll'));
            });

            source.addEventListener('error', () => { source.close(); });

            source.addEventListener('done', (e) => {
                source.close();
                const { content, conversationId } = JSON.parse(e.data);
                $wire.completeStream(index, content || buffer, conversationId);
            });
        });
    </script>
    @endscript
</div>
