<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\client\Client;

class Agency extends Model
{
    protected $fillable = ['code', 'name', 'short_name'];

    public function client() {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relation: Une agence peut avoir plusieurs utilisateurs assignés
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'agency_user')
                    ->withTimestamps()
                    ->withPivot('is_primary', 'assigned_at');
    }
}
