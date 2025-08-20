<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    use HasFactory;

    protected $fillable = ['phone_number', 'metadata'];

    protected $casts = [
        'metadata'  => 'array',
        'campaigns' => 'array'
    ];

    public function scopeFindByPhoneNumber($query, $phoneNumber)
    {
        return $query->where('phone_number', 'like', '%' . $phoneNumber);
    }

    /**
     * Retrieve a patient profile by phone number
     *
     * @param string $phoneNumber The phone number to search for
     * @return PatientProfile|null Returns the matching patient profile or null if not found
     */
    public static function getProfileByPhoneNumber($phoneNumber)
    {
        return self::where('phone_number', $phoneNumber)->first();
    }

    /**
     * Add a campaign to the patient profile's campaign history
     * Stores the campaign data along with timestamp and IP address
     * Maintains a maximum of 10 most recent campaigns by removing oldest entries
     *
     * @param array $campaignData The campaign data to be added
     * @return void
     */
    public function addCampaign(array $campaignData)
    {
        $campaignData['date_time'] = date('Y-m-d H:i:s');
        
        $campaigns = $this->campaigns ?? [];
        array_unshift($campaigns, $campaignData);

        // Limit to 10 campaigns
        if (count($campaigns) > 10) {
            array_pop($campaigns);
        }

        $this->campaigns = $campaigns;
        $this->save();
    }

    /**
     * Get patient opt-in status from most recent campaign
     *
     * Retrieves the patient profile by phone number and returns the opt-in status
     * from their most recent campaign. If no campaigns exist or the status is not set,
     * returns null.
     *
     * @param string $phoneNumber The phone number to search for
     * @return array Returns array containing opt-in status from most recent campaign
     */
    public static function getPatientOptInStatus($phoneNumber)
    {
        $patientProfile = self::where('phone_number', $phoneNumber)->first();
        if (!$patientProfile || !$patientProfile->campaigns) {
            return ['status' => null];
        }
        $campaigns = $patientProfile->campaigns;
        $status = isset($campaigns[0]['opt_in_status']) ? $campaigns[0]['opt_in_status'] : null;
        return ['status' => $status];
    }

}