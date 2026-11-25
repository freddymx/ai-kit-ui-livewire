<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Livewire\Livewire;
use SolutionForest\AiKit\Ui\Livewire\Chat;
use SolutionForest\AiKit\Ui\Tests\Support\User;

uses(RefreshDatabase::class);

/**
 * A conversation owned by the given participant.
 */
function seedConversation(User $owner, string $title): string
{
    $store = app(ConversationStore::class);

    $id = $store->storeConversation(
        Conversation::participantType($owner),
        Conversation::participantKey($owner),
        $title,
    );

    ConversationMessage::create([
        'id' => (string) Illuminate\Support\Str::uuid7(),
        'conversation_id' => $id,
        'participant_type' => Conversation::participantType($owner),
        'participant_id' => Conversation::participantKey($owner),
        'agent' => SolutionForest\AiKit\Agents\RagAgent::class,
        'role' => 'assistant',
        'content' => 'Answer for ' . $title,
        'attachments' => [],
        'steps' => [],
        'usage' => [],
        'meta' => [],
        'status' => MessageStatus::Completed,
    ]);

    return $id;
}

it('lists only the signed-in participant conversations', function (): void {
    $me = User::create(['name' => 'Me']);
    $other = User::create(['name' => 'Someone else']);

    seedConversation($me, 'Mine');
    seedConversation($other, 'Theirs');

    $this->actingAs($me);

    $list = Livewire::test(Chat::class, ['history' => true])->instance()->conversationList();

    expect($list)->toHaveCount(1)
        ->and($list[0]['title'])->toBe('Mine');
});

it('does not load a conversation owned by another participant', function (): void {
    $me = User::create(['name' => 'Me']);
    $other = User::create(['name' => 'Someone else']);

    $theirs = seedConversation($other, 'Theirs');

    $this->actingAs($me);

    $component = Livewire::test(Chat::class, ['history' => true])
        ->call('loadConversation', $theirs);

    expect($component->get('messages'))->toBe([])
        ->and($component->get('conversationId'))->toBeNull();
});

it('loads the messages of a conversation owned by the signed-in participant', function (): void {
    $me = User::create(['name' => 'Me']);

    $mine = seedConversation($me, 'Mine');

    $this->actingAs($me);

    $component = Livewire::test(Chat::class, ['history' => true])
        ->call('loadConversation', $mine);

    expect($component->get('conversationId'))->toBe($mine)
        ->and($component->get('messages'))->toHaveCount(1)
        ->and($component->get('messages')[0]['content'])->toContain('Answer for Mine');
});

it('deletes an owned conversation with its messages and removes it from the list', function (): void {
    $me = User::create(['name' => 'Me']);

    $mine = seedConversation($me, 'Mine');

    $this->actingAs($me);

    $component = Livewire::test(Chat::class, ['history' => true])
        ->call('deleteConversation', $mine);

    expect(Conversation::query()->whereKey($mine)->exists())->toBeFalse()
        ->and(ConversationMessage::query()->where('conversation_id', $mine)->exists())->toBeFalse()
        ->and($component->instance()->conversationList())->toBe([]);
});

it('does not delete a conversation owned by another participant', function (): void {
    $me = User::create(['name' => 'Me']);
    $other = User::create(['name' => 'Someone else']);

    $theirs = seedConversation($other, 'Theirs');

    $this->actingAs($me);

    Livewire::test(Chat::class, ['history' => true])
        ->call('deleteConversation', $theirs);

    expect(Conversation::query()->whereKey($theirs)->exists())->toBeTrue()
        ->and(ConversationMessage::query()->where('conversation_id', $theirs)->exists())->toBeTrue();
});

it('resets the active conversation when it is deleted', function (): void {
    $me = User::create(['name' => 'Me']);

    $mine = seedConversation($me, 'Mine');

    $this->actingAs($me);

    $component = Livewire::test(Chat::class, ['history' => true])
        ->call('loadConversation', $mine)
        ->set('draft', 'Unsent draft')
        ->call('deleteConversation', $mine);

    expect($component->get('messages'))->toBe([])
        ->and($component->get('conversationId'))->toBeNull()
        ->and($component->get('draft'))->toBe('');
});

it('does nothing without an authenticated user', function (): void {
    $me = User::create(['name' => 'Me']);

    $mine = seedConversation($me, 'Mine');

    Livewire::test(Chat::class, ['history' => true])
        ->call('deleteConversation', $mine);

    expect(Conversation::query()->whereKey($mine)->exists())->toBeTrue();
});

it('renders the delete button with a confirmation prompt', function (): void {
    $me = User::create(['name' => 'Me']);

    $mine = seedConversation($me, 'Mine');

    $this->actingAs($me);

    Livewire::test(Chat::class, ['history' => true])
        ->assertSee('wire:confirm="Delete this conversation?"', false)
        ->assertSee("wire:click.stop=\"deleteConversation('{$mine}')\"", false);
});
