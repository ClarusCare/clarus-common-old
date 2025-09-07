<?php

namespace ClarusCommon\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Eloquent
{
    use HasFactory, PreviousTimestampFormat;

    public static $rules = [];

    protected $guarded = [];

    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    public function deferredToProvider()
    {
        return $this->belongsTo(Provider::class, 'deferred_to_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function resolve(): void
    {
        $this->status = 'complete';
        $this->completed_at = \Carbon\Carbon::now();
        $this->save();
    }

    public function resolveWithCall($completedById = null): void
    {
        $this->resolve();

        $this->call->resolve($completedById);
    }
}
