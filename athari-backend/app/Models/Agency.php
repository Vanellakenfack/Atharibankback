<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\client\Client;
class Agency extends Model
{
    protected $fillable = ['code', 'name', 'short_name'];

    public function client() {
    return $this->belongsTo(Client::class); // Ce profil appartient à un client
}
}
