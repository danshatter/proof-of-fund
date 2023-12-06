<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'amount_earned' => 'integer',
        'amount_remaining' => 'integer'
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'amount_earned' => 0,
        'amount_remaining' => 0
    ];

    /**
     * The relationship with the User model
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
