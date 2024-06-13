<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaiementRecu extends Model
{
    use HasFactory;

    protected $fillable = ['facture_id', 'num_paiement','date_prevu', 'date_reçu', 'montant', 'commentaire','id_paiement', 'sousUtilisateur_id', 'user_id'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($Payement) {
            // Générer le num_paiement s'il n'est pas fourni ou s'il existe déjà
            if (empty($Payement->num_paiement) || self::where('num_paiement', $Payement->num_paiement)->exists()) {
                $Payement->num_paiement = self::generateUniqueNumPayement();
            }
        });
    }

    private static function generateUniqueNumPayement()
    {
        $latestPayement = self::latest('id')->first();
        $nextId = $latestPayement ? $latestPayement->id + 1 : 1;
        $numPayement = str_pad($nextId, 4, '0', STR_PAD_LEFT);

        while (self::where('num_paiement', $numPayement)->exists()) {
            $nextId++;
            $numPayement = str_pad($nextId, 4, '0', STR_PAD_LEFT);
        }
        return $numPayement;
    }    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }

    public function paiement()
    {
        return $this->belongsTo(Payement::class, 'id_paiement');
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
