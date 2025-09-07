<?php

namespace ClarusCommon\Traits;

use App\Models\Call;
use App\Models\Recording;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Transcription;
use App\Models\TranscriptionType;
use App\Models\TranscriptionStatus;
use Illuminate\Support\Facades\Log;
use App\Models\AutomatedTranscription;
use App\Transcription\TranscriptionManager;
use Illuminate\Foundation\Bus\PendingDispatch;
use App\Jobs\CallNotification\InitiateModularJob;
use Clarus\Transcription\Models\TranscriptionJob;
use App\Jobs\Transcription\Watson\TranslateCallJob;
use Clarus\Transcription\Models\DeferredNotification;
use Clarus\Transcription\Constants\TranscriptionJobStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * The uses automated transcription trait.
 *
 * @author Daniel Picado <dpicado@claruscare.com>
 */
trait UsesAutomatedTranscription
{
    /**
     * The message to include on transcriptions if the max length is exceeded.
     *
     * @var string
     */
    protected $maxLengthMessage = '(Content incomplete refer to audio)';

    /**
     * Create transcription job for the given call id.
     *
     * @param  int  $callId
     * @return \Clarus\Transcription\Models\TranscriptionJob
     */
    public function forwardToManualTranscription($callId)
    {
        return TranscriptionJob::updateOrCreate(
            ['call_id' => $callId],
            ['status' => TranscriptionJobStatus::PENDING]
        );
    }

    /**
     * Completes the transcription process and notifies the provider.
     *
     * @param  \App\Models\Call  $call
     * @return void
     */
    protected function completeTranscriptionProcess(Call $call): void
    {
        $deferredNotification = DeferredNotification::where('call_id', $call->id)->first();

        if ($deferredNotification) {
            $deferredNotification->delete();
        }

        Log::info('Dispatching notifications after transcription processing for call ID: '.$call->id);

        if (! $call->active_notification_stream_id) {
            $job = new InitiateModularJob($call);

            $job->notificationStream = $this->setActiveNotificationStreamID($call);

            dispatch($job);
        }
    }

    /**
     * Evaluates an automated transcription and handles it.
     *
     * @param  \App\Models\AutomatedTranscription  $automated
     * @return \App\Models\AutomatedTranscription|Clarus\Transcription\Models\TranscriptionJob
     */
    protected function evaluateTranscription($automated)
    {
        if (! $automated->isConfident()) {
            return $this->handleLowConfidenceCall($automated);
        }

        $automated->markAsTranscribed();

        $this->processCompletedTranscription(Transcription::createFromAutomated($automated));

        return $automated;
    }

    /**
     * Fill the call's fields based on the transcription.
     *
     * @param  \App\Models\Call  $call
     * @param  \App\Models\Transcription  $transcription
     * @return void
     */
    protected function fillCallFromTranscription(Call $call, Transcription $transcription): void
    {
        $message = $this->getMessage($transcription);

        switch ($transcription->type_name) {
            case TranscriptionType::MESSAGE:
                $call->update(['patient_message' => $message]);

                break;

            case TranscriptionType::NAME:
                $call->update(['patient_name' => $message]);

                break;

            case TranscriptionType::DATE_OF_BIRTH:
                $currentDob = $call->patient_dob;

                if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $currentDob)) {
                    $call->update(['patient_dob' => $currentDob]);
                } else {
                    $call->update(['patient_dob' => $message]);
                }

                break;
        }
    }

    /**
     * Get the message to fit in the corresponding filed in a call.
     *
     * @param  \App\Models\Transcription  $transcription
     * @return string
     */
    protected function getMessage(Model $transcription)
    {
        $maxLength = Arr::get(TranscriptionType::MAX_LENGTHS, $transcription->type_name);

        if (! $maxLength || Str::length($transcription->content) <= $maxLength) {
            return $transcription->content;
        }

        $charactersToKeep = $maxLength - Str::length($this->maxLengthMessage);

        $content = Str::substr($transcription->content, 0, $charactersToKeep);

        return "{$content} {$this->maxLengthMessage}";
    }

    /**
     * Get an automated transcription model by sid.
     *
     * @param  string  $sid
     * @return \App\Models\AutomatedTranscription|null
     */
    protected function getTranscriptionBySid($sid)
    {
        return AutomatedTranscription::query()
            ->where('sid', $sid)
            ->with('recording')
            ->first();
    }

    /**
     * Handles an empty transcription.
     *
     * @param  \App\Models\Recording  $recording
     * @param  \App\Models\AutomatedTranscription|null  $transcription
     * @return void
     */
    protected function handleEmptyTranscription(Recording $recording, ?AutomatedTranscription $transcription = null): void
    {
        if (! $transcription) {
            $transcription = AutomatedTranscription::make([
                'call_id'      => $recording->call_id,
                'recording_id' => $recording->id,
                'sid'          => Str::uuid()->toString(),
                'status_name'  => TranscriptionStatus::TRANSCRIBED,
                'type_name'    => AutomatedTranscription::getTypeFromRecording($recording),
                'driver_name'  => app(TranscriptionManager::class)->getDriver(),
            ]);
        }

        if ($recording->recording_url !== '') {
            $transcription->content = config('constants.NO_MESSAGE_LEFT');
        }

        $transcription->confidence = AutomatedTranscription::MAX_CONFIDENCE;

        $this->evaluateTranscription($transcription);
    }

    /**
     * Handles a low confidence automated transcription.
     *
     * @param  \App\Models\AutomatedTranscription  $automated
     * @return \App\Models\AutomatedTranscription|Clarus\Transcription\Models\TranscriptionJob
     */
    protected function handleLowConfidenceCall($automated)
    {
        $type = $automated->type_name;
        $message = $this->getMessage($automated);

        if ($type == 'message' || $type == 'name') {
            $automated->call->update(['patient_' . $type => $message]);
        }

        if ($type == 'date-of-birth') {
            $currentDob = $automated->call->patient_dob;

            if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $currentDob)) {
                $automated->call->update(['patient_dob' => $currentDob]);
            } else {
                $automated->call->update(['patient_dob' => $message]);
            }
        }

        $automated->markAsFailed();

        //$this->completeTranscriptionProcess($automated->call);

        return $automated;
    }

    /**
     * Handles an empty transcription.
     *
     * @param  \App\Models\Recording  $recording
     * @param  \App\Models\AutomatedTranscription|null  $transcription
     * @return void
     */
    protected function handleInaudibleTranscription(Recording $recording, ?AutomatedTranscription $transcription = null): void
    {
        if ($recording->recording_url !== '') {
            $transcription->content = AutomatedTranscription::INAUDIBLE_MESSAGE;
        }

        $transcription->confidence = AutomatedTranscription::MAX_CONFIDENCE;

        $this->evaluateTranscription($transcription);
    }

    /**
     * Handles a low confidence automated transcription.
     *
     * @param  \App\Models\AutomatedTranscription  $automated
     * @return \App\Models\AutomatedTranscription|Clarus\Transcription\Models\TranscriptionJob
     */
    // protected function handleLowConfidenceCall($automated)
    // {
    //     // $message = 'Transcription didn’t meet quality standards. Please listen to the call.';
        
    //     $message = $automated->content;

    //     if ($automated->isTranslatable()) {
    //         $message = "{$message} Note - message in foreign language.";
    //     }

    //     $automated->call->update([
    //         'patient_message' => $message,
    //     ]);

    //     $automated->markAsFailed();

    //     $this->completeTranscriptionProcess($automated->call);

    //     return $automated;
    // }

    /**
     * Checks if there's transcriptions pending for the given call.
     *
     * @param  \App\Models\Call  $call
     * @return bool
     */
    protected function isTranscriptionComplete(Call $call)
    {
        $call->load('automatedTranscriptions');

        $pendingTranscriptions = $call->automatedTranscriptions
            ->filter(function ($transcription) {
                return $transcription->status_name !== TranscriptionStatus::COMPLETE
                    && $transcription->status_name !== TranscriptionStatus::TRANSCRIBED;
            })->count();

        return $pendingTranscriptions === 0;
    }

    /**
     * Handles a completed transcription.
     *
     * @param  \App\Models\Transcription  $transcription
     * @return void | \Illuminate\Foundation\Bus\PendingDispatch
     */
    protected function processCompletedTranscription(Transcription $transcription)
    {
        $transcription->load('recording.call');

        $call = $transcription->recording->call;

        $this->fillCallFromTranscription($call, $transcription);

        if ($transcription->driver_name != 'whisper' && !$this->isTranscriptionComplete($call)) {
            return;
        }

        // Below code is used for translating the spanish call through Watson service.
        // Commented the below code because this is not being useful now, as translation has been already done through rev-ai service.
        // if (! $transcription->recording->call->isEnglish()) {
        //     // return TranslateCallJob::dispatch($call);    
        //     return (new TranslateCallJob($call))->dispatch();
        // }

        // if ($this->notify) {
        //     $this->completeTranscriptionProcess($call);
        // }
    }

    /**
     * Set the call's active notification stream id.
     *
     * @param  \App\Models\Call  $call
     * @return string
     */
    protected function setActiveNotificationStreamID($call)
    {
        $call->update([
            'active_notification_stream_id' => $id = Str::random(32),
        ]);

        return $id;
    }
}
