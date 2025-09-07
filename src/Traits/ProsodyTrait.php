<?php

namespace ClarusCommon\Traits;


use Illuminate\Support\Facades\Log;
use Twilio\TwiML\Voice\SsmlProsody;

/**
 * append say with prosody
 *
 */
trait ProsodyTrait
{
    public function appendSayWithProsody($gather, $message, $options = []) {
        $prosodyAttributes = [
            'rate' => config('twilio.twilio.prosody_rate') // Set the rate attribute to 0.85 for slowing down the voice
        ];

        $prosodyElement = new SsmlProsody($message, $prosodyAttributes);
        $sayElement = $gather->say("",$options);
        $sayElement->append($prosodyElement);

        Log::info("appendSayWithProsody function called:::",["sayelement"=>$sayElement]);
        return $sayElement;
    }
}
