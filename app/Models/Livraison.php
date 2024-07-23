<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Livraison extends Model
{
    use HasFactory;
    protected $fillable = [
        'num_livraison',
        'date_livraison', 
        'titre',
        'description',
        'prix_HT',
        'prix_TTC',
        'note_livraison',
        'reduction_livraison',
        'active_Stock',
        'statut_livraison',
        'archiver',
        'client_id',
        'facture_id',
        'fournisseur_id',
        'sousUtilisateur_id',
        'user_id',
        'id_comptable',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    // Relation avec les articles de la facture
    public function articles()
    {
        return $this->hasMany(ArtcleFacture::class);
    }
    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function generateNumLivraison($id)
    {
        $year = date('Y');
        return 'L' . $year . '00' . $id;
    }

}
