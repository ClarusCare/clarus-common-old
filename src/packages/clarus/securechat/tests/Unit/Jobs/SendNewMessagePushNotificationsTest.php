<?php

use Mockery as m;

class SendNewMessagePushNotificationsTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function test_within_time_constraint_returns_false_when_time_since_message_exceeds_constraint(): void
    {
        $job = m::mock(\Clarus\SecureChat\Jobs\SendNewMessagePushNotifications::class)->makePartial();
        $message = m::mock(\Clarus\SecureChat\Models\ChatMessage::class);

        $createdAt = \Carbon\Carbon::now()->subMinutes(20);

        $message->shouldReceive('getAttribute')
            ->with('created_at')
            ->andReturn($createdAt);

        $result = $job->isWithinTimeConstraint($message);

        $this->assertFalse($result);
    }

    public function test_within_time_constraint_returns_true_when_time_since_message_is_within_constraint(): void
    {
        $job = m::mock(\Clarus\SecureChat\Jobs\SendNewMessagePushNotifications::class)->makePartial();
        $message = m::mock(\Clarus\SecureChat\Models\ChatMessage::class);

        $createdAt = \Carbon\Carbon::now()->subMinutes(1);

        $message->shouldReceive('getAttribute')
            ->with('created_at')
            ->andReturn($createdAt);

        $result = $job->isWithinTimeConstraint($message);

        $this->assertTrue($result);
    }
}
