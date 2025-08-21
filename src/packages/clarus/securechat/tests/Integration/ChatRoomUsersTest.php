<?php

namespace Clarus\SecureChat\Tests\Integration;

class ChatRoomUsersTest extends BrowserKitTestCase
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

    public function test_delete_chat_room_users_returns_error_on_bad_user_id(): void
    {
        $response = $this->call('DELETE', $this->routePrefix.'/chat-rooms/1/users/2', [], [], [], $this->headers);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_delete_chat_room_users_returns_error_on_nonexistent_room(): void
    {
        $response = $this->call('DELETE', $this->routePrefix.'/chat-rooms/65/users/4', [], [], [], $this->headers);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_delete_chat_room_users_returns_error_on_user_not_in_room(): void
    {
        $response = $this->call('DELETE', $this->routePrefix.'/chat-rooms/2/users/4', [], [], [], $this->headers);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test_delete_chat_room_users_returns_success(): void
    {
        $response = $this->call('DELETE', $this->routePrefix.'/chat-rooms/1/users/4', [], [], [], $this->headers);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
