<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'date_conversation',
        'interlocuteur',
        'objet',
        'message_conversation',
        'statut',
        'client_id',
        'sousUtilisateur_id',
        'user_id',
       
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
