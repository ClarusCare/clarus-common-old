<?php

namespace Clarus\SecureChat\Tests\Integration;

class ChatMessagesTest extends BrowserKitTestCase
{
    protected $headers;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->seed('Clarus\SecureChat\Database\Seeds\Test\ChatRoomsTestTableSeeder');
        $this->seed('Clarus\SecureChat\Database\Seeds\Test\ChatMessagesTestTableSeeder');

        $this->headers = ['HTTP_Authorization' => "Bearer {$this->getOfficeManagerAccessToken()}"];
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_get_chat_room_messages_page_two_returns_messages(): void
    {
        $this->get($this->routePrefix.'/chat-rooms/1/messages?latest_message_id=25&direction=next', $this->headers)
            ->seeJsonContains(['content' => 'This is seeded message number 30.'])
            ->seeJsonContains(['count' => 25])
            ->seeJsonContains(['remaining' => 51])
            ->seeStatusCode(200);
    }

    public function test_get_chat_room_messages_returns_messages(): void
    {
        $this->get($this->routePrefix.'/chat-rooms/1/messages?direction=next', $this->headers)
            ->seeJsonContains(['content' => 'This is seeded message number 2.'])
            ->seeJsonContains(['count' => 25])
            ->seeJsonContains(['remaining' => 75])
            ->seeStatusCode(200);
    }

    public function test_get_chat_room_messages_returns_not_authorized_for_user_not_in_room(): void
    {
        $this->get($this->routePrefix.'/chat-rooms/2/messages', $this->headers)
            ->seeJsonContains(['error' => 'unauthorized'])
            ->seeStatusCode(403);
    }

    public function test_get_chat_room_messages_with_per_page_returns_messages(): void
    {
        $this->get($this->routePrefix.'/chat-rooms/1/messages?per_page=20&direction=next', $this->headers)
            ->seeJsonContains(['content' => 'This is seeded message number 2.'])
            ->seeJsonContains(['count' => 20])
            ->seeJsonContains(['remaining' => 80])
            ->seeStatusCode(200);
    }

    public function test_get_message_returns_message(): void
    {
        $this->get($this->routePrefix.'/messages/2', $this->headers)
            ->seeJsonContains(['content' => 'This is seeded message number 2.'])
            ->seeStatusCode(200);
    }

    public function test_get_message_returns_not_authorized_for_user_not_in_room(): void
    {
        $this->get($this->routePrefix.'/messages/1', $this->headers)
            ->seeJsonContains(['error' => 'unauthorized'])
            ->seeStatusCode(403);
    }

    public function test_get_non_exist_message_returns_404(): void
    {
        $this->get($this->routePrefix.'/messages/9999', $this->headers)
            ->seeStatusCode(404);
    }

    public function test_post_messages_returns_success(): void
    {
        $this->post($this->routePrefix.'/chat-rooms/1/messages', [
            'content' => 'This is a test message.',
        ], $this->headers)
            ->seeJsonContains(['content' => 'This is a test message.'])
            ->seeInDatabase('chat_messages', ['content' => 'This is a test message.'])
            ->seeStatusCode(200);
    }

    public function test_put_messages_mark_as_read_returns_success(): void
    {
        $this->put($this->routePrefix.'/messages/mark-as-read', [
            'message_ids' => [1,2,3,4,5,6],
        ], $this->headers)
            ->seeJsonContains(['success' => true])
            ->seeInDatabase('chat_message_user', ['chat_message_id' => 1, 'user_id' => 4])
            ->seeStatusCode(200);
    }

    public function test_put_messages_mark_as_read_with_non_existent_message_id_returns_error(): void
    {
        $this->put($this->routePrefix.'/messages/mark-as-read', [
            'message_ids' => ['9999'],
        ], $this->headers)
            ->seeJsonContains(['error' => 'mark_as_read_failure'])
            ->seeStatusCode(400);
    }

    public function test_put_messages_mark_as_read_without_message_ids_returns_error(): void
    {
        $this->put($this->routePrefix.'/messages/mark-as-read', [], $this->headers)
            ->seeJsonContains(['message_ids' => ['The message ids field is required.']])
            ->seeStatusCode(422);
    }
}
