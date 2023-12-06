<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The ID of the user
     */
    public const USER = 1;

    /**
     * The ID of the individual agent
     */
    public const INDIVIDUAL_AGENT = 2;

    /**
     * The ID of the agency
     */
    public const AGENCY = 3;

    /**
     * The ID of the administrator
     */
    public const ADMINISTRATOR = 4;

    /**
     * The relationship with the User model
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
