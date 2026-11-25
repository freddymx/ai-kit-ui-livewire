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

            /* Header — Ethereal Glass double-bezel island. The outer tray is
               machined, the inner core floats. Only transform/opacity animate;
               no blur on scrolling content. */
            .ck-root{--ck-header-tint:#f6f8fc;--ck-header-text:#0b1220;--ck-header-hair:rgba(11,18,32,.07);--ck-header-glow:rgba(37,99,235,.10);--ck-header-ring:rgba(37,99,235,.36);--ck-header-status:#10b981}
            @media (prefers-color-scheme:dark){.ck-root{--ck-header-tint:#0e1424;--ck-header-text:#e8edf7;--ck-header-hair:rgba(255,255,255,.08);--ck-header-glow:rgba(99,102,241,.16);--ck-header-ring:rgba(129,140,248,.42);--ck-header-status:#34d399}}
            .ck-header-shell{position:relative;isolation:isolate;flex:0 0 auto;margin:12px 12px 0;padding:6px;border-radius:20px;background:linear-gradient(135deg,var(--ck-header-glow),var(--ck-header-tint) 42%,var(--ck-header-tint));box-shadow:inset 0 0 0 1px var(--ck-header-hair);animation:ck-header-in .8s cubic-bezier(.32,.72,0,1) both}
            .ck-header-shell::before{content:"";position:absolute;inset:0;border-radius:inherit;background:radial-gradient(120% 180% at 12% -30%,var(--ck-header-glow),transparent 64%);transform:translateZ(0);animation:ck-header-halo 9s cubic-bezier(.32,.72,0,1) infinite alternate}
            @keyframes ck-header-in{from{opacity:0;transform:translateY(-10px) scale(.985)}to{opacity:1;transform:translateY(0) scale(1)}}
            @keyframes ck-header-halo{from{opacity:.5;transform:translate3d(0,0,0) scale(1)}to{opacity:1;transform:translate3d(2%,1%,0) scale(1.05)}}
            .ck-header{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;border-radius:14px;background:var(--ck-bg);box-shadow:inset 0 1px 0 rgba(255,255,255,.45),0 18px 40px -24px rgba(15,23,42,.28)}
            .ck-header-identity{min-width:0;display:flex;flex-direction:column;gap:4px;animation:ck-header-rise .8s cubic-bezier(.32,.72,0,1) .08s both}
            .ck-header-actions{display:flex;align-items:center;gap:8px;flex:0 0 auto;animation:ck-header-rise .8s cubic-bezier(.32,.72,0,1) .16s both}
            @keyframes ck-header-rise{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
            .ck-header-eyebrow{display:inline-flex;align-items:center;gap:6px;width:max-content;max-width:100%;padding:3px 9px;border-radius:999px;background:var(--ck-header-tint);box-shadow:inset 0 0 0 1px var(--ck-header-hair);color:var(--ck-muted);font-size:.62rem;font-weight:600;letter-spacing:.2em;text-transform:uppercase}
            .ck-header-eyebrow-dot{width:5px;height:5px;border-radius:50%;background:var(--ck-accent);box-shadow:0 0 0 3px var(--ck-header-glow)}
            .ck-header-title{margin:0;font-family:"Geist","Plus Jakarta Sans",ui-sans-serif,system-ui,sans-serif;font-size:1.06rem;font-weight:600;letter-spacing:-.035em;line-height:1.15;color:var(--ck-header-text);text-wrap:balance}
            .ck-header-sub{margin:0;font-size:.72rem;color:var(--ck-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
            .ck-header-status{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:999px;background:var(--ck-header-tint);box-shadow:inset 0 0 0 1px var(--ck-header-hair);font-size:.68rem;font-weight:600;color:var(--ck-muted)}
            .ck-header-status-dot{position:relative;width:6px;height:6px;border-radius:50%;background:var(--ck-header-status)}
            .ck-header-status-dot::after{content:"";position:absolute;inset:-3px;border-radius:inherit;border:1px solid var(--ck-header-ring);opacity:0;animation:ck-status-ring 2.6s cubic-bezier(.32,.72,0,1) infinite}
            @keyframes ck-status-ring{0%{transform:scale(.6);opacity:.7}70%,100%{transform:scale(1.8);opacity:0}}
            .ck-header-new{display:inline-flex;align-items:center;gap:8px;padding:5px 5px 5px 14px;border:0;border-radius:999px;background:var(--ck-accent);color:#fff;font:inherit;font-size:.74rem;font-weight:600;cursor:pointer;transition:transform .5s cubic-bezier(.32,.72,0,1),box-shadow .5s cubic-bezier(.32,.72,0,1)}
            .ck-header-new:hover{transform:translateY(-2px);box-shadow:0 14px 28px -14px var(--ck-accent)}
            .ck-header-new:active{transform:scale(.96)}
            .ck-header-new-icon{width:26px;height:26px;border-radius:999px;background:rgba(255,255,255,.16);box-shadow:inset 0 1px 0 rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;transition:transform .55s cubic-bezier(.32,.72,0,1)}
            .ck-header-new:hover .ck-header-new-icon{transform:translate(2px,-2px) scale(1.06) rotate(-8deg)}
            .ck-header-new-icon svg{width:14px;height:14px}
            @media (max-width:768px){.ck-header-shell{margin:8px 8px 0;padding:5px;border-radius:16px}.ck-header{gap:10px;padding:10px 12px;border-radius:11px}.ck-header-eyebrow{letter-spacing:.12em;font-size:.56rem}.ck-header-title{font-size:.94rem}.ck-header-sub{display:none}.ck-header-new-label{display:none}.ck-header-new{padding:5px}.ck-header-status{padding:5px 8px}}
            @media (prefers-reduced-motion:reduce){.ck-header-shell,.ck-header-shell::before,.ck-header-identity,.ck-header-actions,.ck-header-status-dot::after{animation:none!important}.ck-header-new,.ck-header-new-icon{transition:none!important}}
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
        <div class="ck-header-shell">
            <header class="ck-header">
                <div class="ck-header-identity">
                    <span class="ck-header-eyebrow"><span class="ck-header-eyebrow-dot"></span> AI Kit · Grounded</span>
                    <h2 class="ck-header-title">Knowledge assistant</h2>
                    <p class="ck-header-sub">{{ $collection ?: config('ai-kit.default_collection', 'default') }} · sources cited</p>
                </div>
                <div class="ck-header-actions">
                    <span class="ck-header-status" title="Answers are grounded in your knowledge base">
                        <span class="ck-header-status-dot"></span>
                        <span>Live</span>
                    </span>
                    <button type="button" class="ck-header-new" wire:click="newConversation"
                            aria-label="Start a new conversation" title="Start a new conversation">
                        <span class="ck-header-new-label">New chat</span>
                        <span class="ck-header-new-icon">
                            <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" />
                            </svg>
                        </span>
                    </button>
                </div>
            </header>
        </div>

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
