<?php

namespace Clarus\SecureChat\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Clarus\SecureChat\Models\ChatRoom;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\Resources\ApnsConfig;
use NotificationChannels\Fcm\Resources\AndroidConfig;
use NotificationChannels\Fcm\Resources\ApnsFcmOptions;
use NotificationChannels\Fcm\Resources\AndroidFcmOptions;
use NotificationChannels\Fcm\Resources\AndroidNotification;

class NewMessagePushRequested extends Notification
{
    /**
     * @var ChatRoom
     */
    private $chatRoom;

    /**
     * Create a new notification instance.
     *
     * @param  ChatRoom  $chatRoom
     */
    public function __construct(ChatRoom $chatRoom)
    {
        $this->chatRoom = $chatRoom;
    }

    /**
     * @param $notifiable
     * @return mixed
     */
    public function toFcm($notifiable)
    {
        if (! $notifiable instanceof User) {
            return false;
        }

        Log::info('Trying secure chat FCM push notification to user #'.$notifiable->id);

        try {
            $messageData = $this->getMessageData($notifiable);

            return FcmMessage::create()
                ->setData($messageData)
                ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
                ->setTitle($this->chatRoom->partner->name)
                ->setBody('New Secure Chat Message'))
                ->setAndroid(
                    AndroidConfig::create()
                        ->setFcmOptions(AndroidFcmOptions::create()->setAnalyticsLabel('analytics'))
                        ->setNotification(AndroidNotification::create()->setColor('#FF8B00'))
                )->setApns(
                    ApnsConfig::create()
                        ->setFcmOptions(ApnsFcmOptions::create()->setAnalyticsLabel('analytics_ios'))
                );
        } catch (\Exception $e) {
            Log::info('FCM push notification failed.');
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [
            FcmChannel::class,
        ];
    }

    /**
     * @param  User  $user
     * @return array
     */
    protected function getMessageData(User $user)
    {
        return [
            'custom' => [
                'chat_room_id' => $this->chatRoom->id,
            ],
            'chat_room_id' => $this->chatRoom->id,
        ];
    }
}
