<?php

namespace ClarusSharedModels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerEhrSetting extends Model
{
    use HasFactory;

    // Specify the table name if it differs from the pluralized model name
    protected $table = 'partner_ehr_settings';

    /**
     * Get the Partner model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToOne
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the EhrDriver model relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToOne
     */
    public function ehrDriver()
    {
        return $this->hasOne(EhrDriver::class, 'id', 'ehr_id');
    }

    /**
     * Checks if EHR (Electronic Health Records) configurations are configured completely for the specific id.
     *
     * This method determines whether the current EHR settings is configured with username/password/destinationId.
     * @return bool True if EHR is configured, false otherwise.
     */
    public function ehrConfigured(): bool
    {
        $username = $this->username;
        $password = $this->password;
        $dest_id  = $this->destination_id;

        // Check if any of the fields are null or empty
        if (is_null($username) || trim($username) === '' || is_null($password) || trim($password) === '' || is_null($dest_id) || trim($dest_id) === '') {
            return false;
        }
        return true;
    }

    public static $rules = [
        'partner_id' => 'required',
        'ehr_id'     => 'required',
    ];

    protected $fillable = ['ehr_id', 'partner_id', 'username', 'password', 'destination_id', 'note_types', 'metadata', 'modified_by', 'url', 'service_application_id', 'service_facility_id'];

    protected $hidden = ['ehr_id', 'partner_id'];
}
