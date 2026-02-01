<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\CreditApplication;

class Avis extends Model
{
    use HasFactory;

    protected $table = 'avis';

    protected $fillable = [
        'credit_application_id',
        'user_id',
        'opinion',
        'commentaire',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class);
    }
}
