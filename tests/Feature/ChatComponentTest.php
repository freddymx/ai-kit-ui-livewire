<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use SolutionForest\AiKit\Agents\RagAgent;
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
        ->assertSee('font-family:ui-sans-serif,system-ui,-apple-system,sans-serif;text-align:left}', false)
        ->assertSee('.ck-bubble{padding:10px 14px;border-radius:var(--ck-radius);line-height:1.55;font-size:.95rem;text-align:left;', false);
});
