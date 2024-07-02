<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $fillable = [
        'num_client',
        'nom_client',
        'prenom_client',
        'nom_entreprise',
        'email_client',
        'adress_client',
        'tel_client',
        'sousUtilisateur_id',
        'categorie_id',
        'user_id',
        'num_id_fiscal',
        'type_client',
        'statut_client',
        'code_postal_client',
        'ville_client',
        'pays_client',
        'noteInterne_client',
        'nom_destinataire',
        'pays_livraison',
        'ville_livraison',
        'code_postal_livraison',
        'tel_destinataire',
        'email_destinataire',
        'infoSupplemnt',
        'id_comptable'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($Client) {
            // Générer le num_client s'il n'est pas fourni ou s'il existe déjà
            if (empty($Client->num_client) || self::where('num_client', $Client->num_client)->exists()) {
                $Client->num_client = self::generateUniqueNumClient();
            }
        });
    }

    private static function generateUniqueNumClient()
    {
        $latestClient = self::latest('id')->first();
        $nextId = $latestClient ? $latestClient->id + 1 : 1;
        $numClient = str_pad($nextId, 6, '0', STR_PAD_LEFT);

        while (self::where('num_client', $numClient)->exists()) {
            $nextId++;
            $numClient = str_pad($nextId, 6, '0', STR_PAD_LEFT);
        }
        return $numClient;
    }

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categorie()
    {
        return $this->belongsTo(CategorieClient::class, 'categorie_id');
    }
    public function factures()
    {
        return $this->hasMany(Facture::class);
    }

    public function devis()
    {
        return $this->hasMany(Devi::class);
    }

    public function livraison()
    {
        return $this->hasMany(Livraison::class);
    }
    public function BonCommande()
    {
        return $this->hasMany(BonCommande::class);
    }

    public function GrilleTarifaire()
    {
        return $this->hasMany(GrilleTarifaire::class);
    }

    public function compteComptable()
    {
        return $this->belongsTo(CompteComptable::class, 'id_comptable');
    }
}
