<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;



class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    // Utiliser 'web' pour l'auth par session (interface web). Mobile/API pourra être ajouté plus tard.
    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relation: Un utilisateur peut être assigné à plusieurs agences
     */
    public function agencies()
    {
        return $this->belongsToMany(\App\Models\Agency::class, 'agency_user')
                    ->withTimestamps()
                    ->withPivot('is_primary', 'assigned_at');
    }

    /**
     * Helper: Récupérer l'agence primaire de l'utilisateur
     */
    public function getPrimaryAgency()
    {
        return $this->agencies()->wherePivot('is_primary', true)->first();
    }

    /**
     * Helper: Récupérer l'agence_id (compatibilité avec le code existant)
     * Retourne l'id de l'agence primaire ou la première agence assignée
     */
    public function getAgenceIdAttribute()
    {
        $primary = $this->getPrimaryAgency();
        if ($primary) {
            return $primary->id;
        }
        
        // Fallback: première agence assignée
        return $this->agencies()->first()?->id;
    }
    
}
