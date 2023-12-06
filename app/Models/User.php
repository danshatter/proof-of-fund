<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\Auth\{UsesOtp, UsesReferralCode};

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, UsesOtp, UsesReferralCode;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'address',
        'request_message',
        'business_name',
        'business_website',
        'business_state',
        'password',
        'residential_address',
        'state_of_residence',
        'proof_of_residence_image',
        'proof_of_residence_image_url',
        'proof_of_residence_image_driver'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'first_name',
        'last_name',
        'date_of_birth',
        'address',
        'request_message',
        'business_name',
        'business_website',
        'business_state',
        'password',
        'remember_token',
        'email_verified_at',
        'email_verification',
        'image',
        'verified_at',
        'verification',
        'verification_expires_at',
        'failed_login_attempts',
        'failed_verification_attempts',
        'locked_due_to_failed_verification_at',
        'locked_due_to_failed_login_attempts_at',
        'residential_address',
        'state_of_residence',
        'proof_of_residence_image',
        'proof_of_residence_image_url',
        'proof_of_residence_image_driver',
        'referred_by',
        'referral_code'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role_id' => 'integer',
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date:Y-m-d',
        'verified_at' => 'datetime',
        'verification' => 'encrypted',
        'verification_expires_at' => 'datetime',
        'failed_verification_attempts' => 'integer',
        'locked_due_to_failed_verification_at' => 'datetime',
        'failed_login_attempts' => 'integer',
        'locked_due_to_failed_login_attempts_at' => 'datetime',
        'referred_by' => 'integer'
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'role_id' => Role::USER
    ];

    /**
     * Scope for users
     */
    public function scopeUsers($query)
    {
        return $query->where('role_id', Role::USER);
    }

    /**
     * Scope for individual agents
     */
    public function scopeIndividualAgents($query)
    {
        return $query->where('role_id', Role::INDIVIDUAL_AGENT);
    }

    /**
     * Scope for agencies
     */
    public function scopeAgencies($query)
    {
        return $query->where('role_id', Role::AGENCY);
    }

    /**
     * Scope for administrators
     */
    public function scopeAdministrators($query)
    {
        return $query->where('role_id', Role::ADMINISTRATOR);
    }

    /**
     * The relationship with the Role model
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * The relationship with the Camouflage model
     */
    public function camouflage()
    {
        return $this->hasOne(Camouflage::class);
    }

    /**
     * The relationship with the Application model
     */
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    /**
     * The relationship with the Card model
     */
    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    /**
     * The relationship with the Account model
     */
    public function account()
    {
        return $this->hasOne(Account::class);
    }

    /**
     * The relationship with the Balance model
     */
    public function balance()
    {
        return $this->hasOne(Balance::class);
    }

    /**
     * The relationship with the Activity model
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * The relationship with the User model as regards to the referrer
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * The relationship with the User model as regards to users referred
     */
    public function referred()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        switch ($this->role_id) {
            case Role::USER:
                $this->makeVisible([
                    'first_name',
                    'last_name',
                    'date_of_birth',
                    'image'
                ]);
            break;

            case Role::INDIVIDUAL_AGENT:
                $this->makeVisible([
                    'first_name',
                    'last_name',
                    'date_of_birth',
                    'address',
                    'request_message',
                    'image'
                ]);
            break;

            case Role::AGENCY:
                $this->makeVisible([
                    'business_name',
                    'business_website',
                    'business_state',
                    'address',
                    'request_message',
                    'image'
                ]);
            break;
            
            default:
                
            break;
        }

        return parent::toArray();
    }
}
