<?php

namespace Clarus\SecureChat\Tests\Integration;

class AuthenticationTest extends BrowserKitTestCase
{
    protected $headers;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->headers = ['HTTP_Authorization' => "Bearer {$this->getOfficeManagerAccessToken()}"];
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_post_authenticate_to_bad_socket_id_returns_error(): void
    {
        $this->post($this->routePrefix.'/authenticate', [
            'channel_name' => 'private-user_4_channel',
            'socket_id'    => 'not_a_real_socket_id',
        ], $this->headers)
            ->seeJsonContains([
                'socket_id' => ['Invalid socket ID not_a_real_socket_id'],
            ])
            ->seeStatusCode(403);
    }

    public function test_post_authenticate_to_unauthorized_channel_returns_error(): void
    {
        $this->post($this->routePrefix.'/authenticate', [
            'channel_name' => 'bogus_user_channel',
            'socket_id'    => '232323232',
        ], $this->headers)
            ->seeJsonContains(['error' => 'unauthorized'])
            ->seeStatusCode(403);
    }

    public function test_post_authenticate_with_good_data_returns_success(): void
    {
        $this->post($this->routePrefix.'/authenticate', [
            'channel_name' => 'private-user_4_channel',
            'socket_id'    => '206799.2209127',
        ], $this->headers)
            ->seeJson()
            ->seeStatusCode(200);
    }

    public function test_post_authenticate_without_data_returns_validation_errors(): void
    {
        $this->post($this->routePrefix.'/authenticate', [], $this->headers)
            ->seeJsonContains([
                'channel_name' => ['The channel name field is required.'],
                'socket_id'    => ['The socket id field is required.'],
            ])
            ->seeStatusCode(422);
    }
}
