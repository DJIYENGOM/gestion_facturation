<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payement extends Model
{
    use HasFactory;

    protected $fillable = ['nom_payement', 'sousUtilisateur_id','user_id'];

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class);
    }
}
