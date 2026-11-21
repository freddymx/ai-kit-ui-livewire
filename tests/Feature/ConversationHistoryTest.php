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
