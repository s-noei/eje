<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'region';

    protected $primaryKey = 'RegionID';

    public $timestamps = false;

    protected $guarded = [];

    public function country()
    {
        return $this->belongsTo(Country::class, 'CountryID', 'CountryID');
    }
}
