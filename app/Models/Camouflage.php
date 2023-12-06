<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auth\UsesOtp;

class Camouflage extends Model
{
    use HasFactory, UsesOtp;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'phone',
        'gender',
        'date_of_birth',
        'confidential',
        'confidential_hash',
        'nationality',
        'image'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'date_of_birth',
        'confidential',
        'confidential_hash',
        'verification',
        'verification_expires_at',
        'failed_verification_attempts',
        'locked_due_to_failed_verification_at',
        'image'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'date_of_birth' => 'date:Y-m-d',
        'confidential' => 'encrypted',
        'verification' => 'encrypted',
        'verified_at' => 'datetime',
        'verification_expires_at' => 'datetime',
        'failed_verification_attempts' => 'integer',
        'locked_due_to_failed_verification_at' => 'datetime'
    ];

    /**
     * The relationship with the User model
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
