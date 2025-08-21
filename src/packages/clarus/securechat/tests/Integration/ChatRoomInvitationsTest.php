<?php

namespace Clarus\SecureChat\Tests\Integration;

class ChatRoomInvitationsTest extends BrowserKitTestCase
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

    public function test_accept_invitation_from_wrong_user_returns_error(): void
    {
        $this->post($this->routePrefix.'/chat-rooms/1/invitations', [
            'user_id' => 1,
        ], $this->headers);

        $this->put($this->routePrefix.'/invitations/1/accept', [], $this->headers)
            ->seeJsonContains(['error' => 'unauthorized'])
            ->seeStatusCode(403);
    }

    public function test_accept_invitation_returns_success(): void
    {
        $this->post($this->routePrefix.'/chat-rooms/1/invitations', [
            'user_id' => 1,
        ], $this->headers);

        $this->refreshApplication();
        $this->headers = ['HTTP_Authorization' => "Bearer {$this->getAccessToken()}"];

        $this->put($this->routePrefix.'/invitations/1/accept', [], $this->headers)
            ->seeJsonContains(['status' => 'Chat room joined.'])
            ->seeInDatabase('chat_room_user', ['user_id' => 1, 'chat_room_id' => 1]) //USER IS IN CHAT ROOM PIVOT TABLE
            ->notSeeInDatabase('chat_room_invitations', ['id' => 1, 'deleted_at' => null]) // INVITATION HAS BEEN SOFT-DELETED
            ->seeStatusCode(200);
    }

    /**
     * User that created an invitation can delete that invitation.
     */
    public function test_delete_invitation_by_created_by_user_returns_success(): void
    {
        $this->post($this->routePrefix.'/chat-rooms/1/invitations', [
            'user_id' => 1,
        ], $this->headers);

        $this->delete($this->routePrefix.'/invitations/1', [], [], [], $this->headers)
            ->notSeeInDatabase('chat_room_invitations', ['id' => 1, 'deleted_at' => null]) // INVITATION HAS BEEN SOFT-DELETED
            ->seeStatusCode(204);
    }

    /**
     * An invited user can delete the invitation.
     */
    public function test_delete_invitation_by_invited_user_returns_success(): void
    {
        $this->post($this->routePrefix.'/chat-rooms/1/invitations', [
            'user_id' => 1,
        ], $this->headers);

        $this->refreshApplication();
        $this->headers = ['HTTP_Authorization' => "Bearer {$this->getAccessToken()}"];

        $response = $this->call('DELETE', $this->routePrefix.'/invitations/1', [], [], [], $this->headers);
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function test_delete_invitation_from_non_invited_and_non_created_by_user_returns_error(): void
    {
        $this->post($this->routePrefix.'/chat-rooms/1/invitations', [
            'user_id' => 1,
        ], $this->headers);

        $this->refreshApplication();
        $this->headers = ['HTTP_Authorization' => "Bearer {$this->getUserAccessToken()}"];

        $response = $this->call('DELETE', $this->routePrefix.'/invitations/1', [], [], [], $this->headers);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_get_invitations_returns_success(): void
    {
        $this->get($this->routePrefix.'/invitations', $this->headers)
            ->seeJson()
            ->seeStatusCode(200);
    }

    public function test_post_invitations_returns_success_and_creates_record(): void
    {
        $this->post($this->routePrefix.'/chat-rooms/1/invitations', [
            'user_id' => 1,
        ], $this->headers)
            ->seeJsonContains(['chat_room_id' => '1'])
            ->seeInDatabase('chat_room_invitations', ['user_id' => 1, 'chat_room_id' => 1])
            ->seeStatusCode(200);
    }

    public function test_post_invitations_to_self_returns_error(): void
    {
        $this->post($this->routePrefix.'/chat-rooms/1/invitations', [
            'user_id' => 4,
        ], $this->headers)
            ->seeJsonContains(['user_id' => 'User cannot invite self.'])
            ->seeStatusCode(400);
    }

    public function test_post_invitations_to_unaffiliated_user_returns_error(): void
    {
        $this->post($this->routePrefix.'/chat-rooms/1/invitations', [
            'user_id' => 5,
        ], $this->headers)
            ->seeJsonContains(['user_id' => 'Invited user is not authorized.'])
            ->seeStatusCode(400);
    }

    public function test_post_invitations_to_user_already_in_chat_room_returns_error(): void
    {
        $this->post($this->routePrefix.'/chat-rooms/1/invitations', [
            'user_id' => 2,
        ], $this->headers)
            ->seeJsonContains(['user_id' => 'Invited user is already in the requested chat room.'])
            ->seeStatusCode(400);
    }

    public function test_post_invitations_with_non_existent_chat_room_returns_error(): void
    {
        $this->post($this->routePrefix.'/chat-rooms/9999/invitations', [
            'user_id' => 1,
        ], $this->headers)
            ->seeJsonContains(['error' => 'unauthorized'])
            ->seeStatusCode(403);
    }

    public function test_post_invitations_with_non_existent_user_returns_error(): void
    {
        $this->post($this->routePrefix.'/chat-rooms/1/invitations', [
            'user_id' => 9999,
        ], $this->headers)
            ->seeJsonContains(['user_id' => 'User not found.'])
            ->seeStatusCode(400);
    }
}
