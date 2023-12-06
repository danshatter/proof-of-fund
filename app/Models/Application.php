<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    /**
     * For pending applications
     */
    public const PENDING = 'PENDING';

    /**
     * For in review applications
     */
    public const IN_REVIEW = 'IN REVIEW';

    /**
     * For rejected applications
     */
    public const REJECTED = 'REJECTED';

    /**
     * For accepted applications
     */
    public const ACCEPTED = 'ACCEPTED';

    /**
     * For open applications 
     */
    public const OPEN = 'OPEN';

    /**
     * For completed applications
     */
    public const COMPLETED = 'COMPLETED';

    /**
     * For pending installments
     */
    public const INSTALLMENT_PENDING = 'PENDING';

    /**
     * For open installments
     */
    public const INSTALLMENT_OPEN = 'OPEN';

    /**
     * For closed installments
     */
    public const INSTALLMENT_CLOSED = 'CLOSED';

    /**
     * For overdue installments
     */
    public const INSTALLMENT_OVERDUE = 'OVERDUE';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'amount',
        'amount_remaining',
        'tenure',
        'type',
        'interest',
        'state_of_origin',
        'residential_address',
        'state_of_residence',
        'proof_of_residence_image',
        'proof_of_residence_image_url',
        'proof_of_residence_image_driver',
        'travel_purpose',
        'travel_destination',
        'international_passport_number',
        'international_passport_expiry_date',
        'international_passport_image',
        'international_passport_image_url',
        'international_passport_image_driver',
        'guarantor',
        'travel_sponsor',
        'details',
        'active_installment'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'proof_of_residence_image',
        'proof_of_residence_image_driver',
        'international_passport_image',
        'international_passport_image_driver',
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => self::PENDING,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'amount' => 'integer',
        'guarantor' => 'array',
        'travel_sponsor' => 'array',
        'details' => 'array',
        'active_installment' => 'array'
    ];

    /**
     * The relationship with the User model
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The relationship with the Transaction model
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
