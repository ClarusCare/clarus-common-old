<?php

if (!function_exists('scrub_twilio_phone_input')) {
    /**
     * Clean and format phone number for Twilio
     *
     * @param string $phone
     * @return string
     */
    function scrub_twilio_phone_input($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add +1 if it's a 10-digit US number
        if (strlen($phone) === 10) {
            $phone = '1' . $phone;
        }
        
        // Add + prefix
        return '+' . $phone;
    }
}