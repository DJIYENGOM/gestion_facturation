<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fournisseur extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_fournisseur',
        'nom_fournisseur',
        'prenom_fournisseur',
        'nom_entreprise',
        'email_fournisseur',
        'adress_fournisseur',
        'tel_fournisseur',
        'sousUtilisateur_id',
        'user_id',
        'num_id_fiscal',
        'type_fournisseur',
        'code_postal_fournisseur',
        'ville_fournisseur',
        'pays_fournisseur',
        'noteInterne_fournisseur',
        'doc_associer',
        'id_comptable'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($fournisseur) {
            // Générer le num_fournisseur s'il n'est pas fourni ou s'il existe déjà
            if (empty($fournisseur->num_fournisseur) || self::where('num_fournisseur', $fournisseur->num_fournisseur)->exists()) {
                $fournisseur->num_fournisseur = self::generateUniqueNumFournisseur();
            }
        });
    }

    private static function generateUniqueNumFournisseur()
    {
        $latestfournisseur = self::latest('id')->first();
        $nextId = $latestfournisseur ? $latestfournisseur->id + 1 : 1;
        $numfournisseur = str_pad($nextId, 6, '0', STR_PAD_LEFT);

        while (self::where('num_fournisseur', $numfournisseur)->exists()) {
            $nextId++;
            $numfournisseur = str_pad($nextId, 6, '0', STR_PAD_LEFT);
        }
        return $numfournisseur;
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
