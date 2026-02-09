<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditReview extends Model
{
    protected $fillable = [
        'credit_application_id', 
        'user_id', 
        'role_at_vote', 
        'decision', 
        'commentaires'
    ];

    // Relation inverse : l'avis appartient à un dossier
    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class);
    }

    // Relation : l'avis appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}