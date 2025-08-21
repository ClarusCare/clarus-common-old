<?php

namespace Clarus\SecureChat\Jobs;

use Exception;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Clarus\SecureChat\Models\ChatMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Clarus\SecureChat\Constants\ChatUserStatus;
use Clarus\SecureChat\Gateways\ChatRoomGateway;
use Clarus\SecureChat\Notifications\NewMessagePushRequested;

class SendNewMessagePushNotifications implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var ChatMessage
     */
    private $chatMessage;

    /**
     * @var int
     */
    private $chatRoomId;

    /**
     * Maximum number of minutes since message creation
     * that push notification should be sent.
     */
    private $timeout = 5;

    /**
     * PushJob constructor.
     *
     * @param $chatRoomId
     * @param  ChatMessage  $chatMessage
     */
    public function __construct($chatRoomId, ChatMessage $chatMessage)
    {
        $this->chatRoomId = $chatRoomId;
        $this->chatMessage = $chatMessage;

        $this->onQueue('secure-chat');
    }

    /**
     * Handle the job.
     */
    public function handle()
    {
        if (! $this->isWithinTimeConstraint($this->chatMessage)) {
            return false;
        }

        $chatRoomGateway = app(ChatRoomGateway::class);

        try {
            $chatRoom = $chatRoomGateway->find($this->chatRoomId);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        Log::info('Trying secure chat push notification for chat room # '.$chatRoom->id);

        $users = $chatRoom->activeUsers;

        $recipients = $users->filter(function ($user) {
            return $user->id != $this->chatMessage->user_id;
        });

        foreach ($recipients as $recipient) {
            if ($recipient->chat_status == ChatUserStatus::ACTIVE) {
                $apnsTokenCount = $recipient->pushTokens()->where('service', 'apns')->active()->count();
                $fcmTokenCount = $recipient->pushTokens()->where('service', 'fcm')->active()->count();

                if (($apnsTokenCount + $fcmTokenCount) > 0) {
                    $recipient->notify(new NewMessagePushRequested($chatRoom));
                }
            }
        }
    }

    public function isWithinTimeConstraint($message)
    {
        $minsSinceMessageCreation = $message->created_at->diffInMinutes(Carbon::now());

        if ($minsSinceMessageCreation <= $this->timeout) {
            return true;
        }

        return false;
    }
}
