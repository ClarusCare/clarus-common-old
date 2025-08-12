<?php

namespace ClarusSharedModels\Traits;

trait SecureChatUser
{
    /**
     * Initialize the SecureChatUser trait
     */
    public function initializeSecureChatUser()
    {
        // Add any initialization logic here
    }
    
    /**
     * Get the chat channel name for this user
     */
    public function getChatChannelName()
    {
        return 'user.' . $this->id;
    }
}