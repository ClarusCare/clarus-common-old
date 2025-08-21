<?php

namespace Clarus\SecureChat\Events;

use Illuminate\Queue\SerializesModels;

class ChatEventOccurred
{
    use SerializesModels;

    /**
     * @var array
     */
    public $data;

    /**
     * @var string
     */
    public $type;

    /**
     * @var int
     */
    public $userId;

    /**
     * ChatRoomEventOccurred constructor.
     *
     * @param  int  $userId
     * @param  string  $type
     * @param  array  $data
     */
    public function __construct($userId, $type, array $data)
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->data = $data;
    }
}
