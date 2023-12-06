<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * For onboarding
     */
    public const ONBOARDING = 'ONBOARDING';

    /**
     * For credit
     */
    public const DEBIT = 'DEBIT';

    /**
     * For payment
     */
    public const PAYMENT = 'PAYMENT';

    /**
     * For refund
     */
    public const REFUND = 'REFUND';

    /**
     * For withdrawals
     */
    public const WITHDRAWAL = 'WITHDRAWAL';

    /**
     * For referral bonus
     */
    public const REFERRAL_BONUS = 'REFERRAL_BONUS';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'amount',
        'message',
        'reference',
        'transfer_code',
        'recipient_code',
        'customer_code',
        'type',
        'channel',
        'currency'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'application_id' => 'integer',
        'amount' => 'integer'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'transfer_code',
        'recipient_code',
        'customer_code'
    ];

    /**
     * The relationship with the Application model
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
