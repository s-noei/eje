<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A citizen account (legacy `citizens` table). Used by Laravel's auth guard.
 */
class Citizen extends Authenticatable
{
    protected $table = 'citizens';

    protected $primaryKey = 'CitizenID';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token', 'secu_answer'];

    public function getAuthIdentifierName(): string
    {
        return 'CitizenID';
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    /** Legacy rows store MD5 hashes; detect them so we can verify and upgrade. */
    public function hasLegacyPasswordHash(): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/i', (string) $this->password);
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'regionID', 'RegionID');
    }
}
