<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use SolutionForest\AiKit\Agents\RagAgent;
use SolutionForest\AiKit\Models\Collection;
use SolutionForest\AiKit\Ui\Livewire\Chat;

uses(RefreshDatabase::class);

it('renders and defaults to the configured collection', function (): void {
    Livewire::test(Chat::class)
        ->assertSet('collection', 'default')
        ->assertSuccessful();
});

it('shows a create-knowledge-base empty state when the collection has no content', function (): void {
    Livewire::test(Chat::class, ['collection' => 'missing'])
        ->assertSee('No knowledge base yet');
});

it('shows the ask prompt once the collection has embedded content', function (): void {
    seedCollection('kb');

    Livewire::test(Chat::class, ['collection' => 'kb'])
        ->assertSee('Ask anything');
});

it('sends a message and appends a non-streamed assistant reply with citations', function (): void {
    seedCollection('kb');
    config()->set('ai-kit.mode', 'tool');
    RagAgent::fake(['Refunds are processed within fourteen days.']);

    $component = Livewire::test(Chat::class, ['collection' => 'kb'])
        ->set('draft', 'How long do refunds take?')
        ->call('send');

    $messages = $component->get('messages');

    expect($messages)->toHaveCount(2)
        ->and($messages[0]['role'])->toBe('user')
        ->and($messages[1]['role'])->toBe('assistant')
        ->and($messages[1]['content'])->toContain('fourteen days')
        ->and($messages[1]['pending'])->toBeFalse()
        ->and($messages[1]['citations'])->not->toBeEmpty()
        ->and($messages[1]['citations'][0]['document_title'])->toBe('Refunds');

    RagAgent::assertPrompted('How long do refunds take?');
});

it('omits citations when the citations prop is disabled', function (): void {
    seedCollection('kb');
    config()->set('ai-kit.mode', 'tool');
    RagAgent::fake(['Refunds are processed within fourteen days.']);

    $component = Livewire::test(Chat::class, ['collection' => 'kb', 'citations' => false])
        ->assertSet('citations', false)
        ->set('draft', 'How long do refunds take?')
        ->call('send');

    $messages = $component->get('messages');

    expect($messages[1]['content'])->toContain('fourteen days')
        ->and($messages[1]['citations'])->toBe([]);
});

it('ignores an empty draft', function (): void {
    Livewire::test(Chat::class)
        ->set('draft', '   ')
        ->call('send')
        ->assertCount('messages', 0);
});

it('dispatches a stream event instead of answering inline when streaming is on', function (): void {
    seedCollection('kb');
    config()->set('ai-kit.chat.streaming', true);

    Livewire::test(Chat::class, ['collection' => 'kb'])
        ->set('draft', 'How long do refunds take?')
        ->call('send')
        ->assertDispatched('ai-kit:stream')
        ->assertCount('messages', 2);
});

it('pins chat text to the left so host page styles cannot center the bubbles', function (): void {
    Livewire::test(Chat::class)
        ->assertSee('font-family:ui-sans-serif,system-ui,-apple-system,sans-serif;text-align:left!important}', false)
        ->assertSee('.ck-bubble{padding:10px 14px;border-radius:var(--ck-radius);line-height:1.55;font-size:.95rem;text-align:left!important;', false)
        ->assertSee('.ck-messages{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;align-items:stretch;gap:14px}', false)
        ->assertSee('.ck-msg-assistant{align-self:flex-start;align-items:flex-start;margin-right:auto}', false)
        ->assertSee('.ck-msg-user{align-self:flex-end;align-items:flex-end;margin-left:auto}', false)
        ->assertSee('.ck-bubble p,.ck-bubble ul,.ck-bubble ol,.ck-bubble li,', false)
        ->assertSee('.ck-bubble code{text-align:left!important}', false);
});

it('hides the collection selector by default', function (): void {
    Livewire::test(Chat::class)
        ->assertSet('collectionSelector', false)
        ->assertDontSee('class="ck-collection-shell"', false)
        ->assertDontSee('wire:model.live="collection"', false);
});

it('shows the collection selector and its options when opted in', function (): void {
    seedCollection('kb');
    Collection::create(['name' => 'Otra', 'slug' => 'otra']);

    Livewire::test(Chat::class, ['collectionSelector' => true])
        ->assertSet('collectionSelector', true)
        ->assertSee('class="ck-collection-shell"', false)
        ->assertSee('class="ck-collection-select"', false)
        ->assertSee('wire:model.live="collection"', false)
        ->assertSee('value="kb"', false)
        ->assertSee('Kb</option>', false)
        ->assertSee('value="otra"', false)
        ->assertSee('Otra</option>', false);
});

it('takes the collection selector default from config and lets the prop override it', function (): void {
    config()->set('ai-kit.ui.collection_selector', true);

    Livewire::test(Chat::class)
        ->assertSet('collectionSelector', true)
        ->assertSee('class="ck-collection-shell"', false);

    Livewire::test(Chat::class, ['collectionSelector' => false])
        ->assertSet('collectionSelector', false)
        ->assertDontSee('class="ck-collection-shell"', false);
});

it('resets messages, conversation id and draft when the collection changes with the selector visible', function (): void {
    seedCollection('kb');
    Collection::create(['name' => 'Otra', 'slug' => 'otra']);
    config()->set('ai-kit.mode', 'tool');
    RagAgent::fake(['Refunds are processed within fourteen days.']);

    $component = Livewire::test(Chat::class, ['collection' => 'kb', 'collectionSelector' => true])
        ->set('draft', 'How long do refunds take?')
        ->call('send')
        ->set('conversationId', 'conv-1')
        ->set('draft', 'unfinished question')
        ->assertCount('messages', 2);

    $component->set('collection', 'otra')
        ->assertSet('collection', 'otra')
        ->assertCount('messages', 0)
        ->assertSet('conversationId', null)
        ->assertSet('draft', '');
});

it('keeps chat state when the collection changes with the selector hidden', function (): void {
    seedCollection('kb');
    config()->set('ai-kit.mode', 'tool');
    RagAgent::fake(['Refunds are processed within fourteen days.']);

    $component = Livewire::test(Chat::class, ['collection' => 'kb'])
        ->set('draft', 'How long do refunds take?')
        ->call('send')
        ->set('conversationId', 'conv-1')
        ->set('draft', 'unfinished question')
        ->assertCount('messages', 2);

    $component->set('collection', 'otra')
        ->assertSet('collection', 'otra')
        ->assertCount('messages', 2)
        ->assertSet('conversationId', 'conv-1')
        ->assertSet('draft', 'unfinished question');
});

it('pins the collection selector styles to the scoped ck-* rules', function (): void {
    Livewire::test(Chat::class, ['collectionSelector' => true])
        ->assertSee('.ck-collection-shell{flex:0 0 auto;margin:12px 12px 0;padding:5px;border-radius:16px;background:var(--ck-assistant);box-shadow:inset 0 0 0 1px var(--ck-border)}', false)
        ->assertSee('.ck-collection-select:focus-visible{outline:none;box-shadow:inset 0 0 0 1px var(--ck-accent),0 0 0 3px rgba(37,99,235,.12)}', false);
});
