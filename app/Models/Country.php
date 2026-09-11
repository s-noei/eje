<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'country';

    protected $primaryKey = 'CountryID';

    public $timestamps = false;

    protected $guarded = [];

    public function regions()
    {
        return $this->hasMany(Region::class, 'CountryID', 'CountryID');
    }
}
