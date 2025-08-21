<?php

namespace Clarus\SecureChat\Tests\Integration;

class ChatRoomsTest extends BrowserKitTestCase
{
    protected $headers;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->seed('Clarus\SecureChat\Database\Seeds\Test\ChatRoomsTestTableSeeder');

        $this->headers = ['HTTP_Authorization' => "Bearer {$this->getOfficeManagerAccessToken()}"];
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_get_chat_rooms_returns_data(): void
    {
        $this->get($this->routePrefix.'/chat-rooms', $this->headers)
            ->seeJsonContains(['name' => 'Test Room One'])
            ->seeStatusCode(200);
    }

    public function test_post_chat_rooms_returns_success(): void
    {
        $this->post($this->routePrefix.'/chat-rooms', [
            'name'       => 'New Test Room',
            'partner_id' => 1,
        ], $this->headers)
            ->seeJsonContains(['name' => 'New Test Room'])
            ->seeInDatabase('chat_rooms', ['name' => 'New Test Room', 'user_id' => 4])
            ->seeStatusCode(200);
    }

    public function test_post_chat_rooms_without_name_returns_error(): void
    {
        $this->post($this->routePrefix.'/chat-rooms', [], $this->headers)
            ->seeJsonContains(['name' => ['The name field is required.']])
            ->seeStatusCode(422);
    }

    public function test_put_chat_rooms_from_non_owner_returns_error(): void
    {
        $this->put($this->routePrefix.'/chat-rooms/2', [
            'name' => 'Name For Room',
        ], $this->headers)
            ->seeJsonContains(['error' => 'unauthorized'])
            ->seeStatusCode(403);
    }

    public function test_put_chat_rooms_returns_success(): void
    {
        $this->refreshApplication();

        $this->put($this->routePrefix.'/chat-rooms/1', [
            'name' => 'Updated Room Name',
        ], $this->headers)
            ->seeJsonContains(['name' => 'Updated Room Name'])
            ->seeInDatabase('chat_rooms', ['name' => 'Updated Room Name', 'user_id' => 1])
            ->seeStatusCode(200);
    }

    public function test_put_chat_rooms_to_non_existent_id_returns_error(): void
    {
        $this->refreshApplication();
        $this->headers = ['HTTP_Authorization' => "Bearer {$this->getAccessToken()}"];

        $this->put($this->routePrefix.'/chat-rooms/9999', [
            'name' => 'Name For Missing Room',
        ], $this->headers)
            ->seeJsonContains(['error' => 'unauthorized'])
            ->seeStatusCode(403);
    }

    public function test_put_chat_rooms_without_name_returns_error(): void
    {
        $this->refreshApplication();

        $this->put($this->routePrefix.'/chat-rooms/1', [], $this->headers)
            ->seeJsonContains(['name' => ['The name field is required.']])
            ->seeStatusCode(422);
    }
}
