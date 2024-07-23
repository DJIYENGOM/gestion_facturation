<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactureRecurrente extends Model
{
    use HasFactory;

    protected $fillable = [
        'periode',
        'nombre_periode',
        'date_debut',
        'type_reccurente',
        'client_id',
        'creation_automatique',
        'prix_HT',
        'prix_TTC',
        'active_Stock',
        'commentaire',
        'note_interne',
        'sousUtilisateur_id',
        'user_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function articles()
    {
        return $this->hasMany(ArticleFactureAvoir::class, 'id_FactureAvoir');
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class);
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
