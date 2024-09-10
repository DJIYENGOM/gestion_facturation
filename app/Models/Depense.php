<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Depense extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_depense',
        'id_categorie_depense',
        'commentaire',
        'date_paiement',
        'tva_depense',
        'montant_depense_ht',
        'montant_depense_ttc',
        'plusieurs_paiement',
        'duree_indeterminee',
        'periode_echeance',
        'nombre_periode',
        'doc_externe',
        'statut_depense',
        'fournisseur_id',
        'num_facture',
        'date_facture',
        'image_facture',
        'statut_paiement',
        'id_paiement',
        'id_compte_comptable',
        'sousUtilisateur_id',
        'user_id',
    ];

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    public function paiement()
    {
        return $this->belongsTo(Payement::class, 'id_paiement');
    }

    public function categorieDepense()
    {
        return $this->belongsTo(CategorieDepense::class, 'id_categorie_depense');
    }

    public function compteComptable()
    {
        return $this->belongsTo(CompteComptable::class, 'id_compte_comptable');
    }


    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Etiquetttes()
    {
        return $this->hasMany(Facture_Etiquette::class, 'depense_id');
    }


    public static function creerNotification($config, $message, $depense_id)
    {
        $notification = new MessageNotification();
        $notification->message = $message;
        $notification->user_id = $config->user_id;
        $notification->sousUtilisateur_id = $config->sousUtilisateur_id;
        $notification->depense_id = $depense_id;
        $notification->save();
    }

    public static function envoyerNotificationSiImpayer($depense)
    {
        $config = Notification::where('user_id', $depense->user_id)
            ->orWhere('sousUtilisateur_id', $depense->sousUtilisateur_id)
            ->first();

        if ($config && $config->depense_impayer && $config->nombre_jourNotif_depense >= 1) {
            $nombre_jour = $config->nombre_jourNotif_depense;

            $now = Carbon::now();
            $date_paiement = Carbon::parse($depense->date_paiement);

            if ($date_paiement->isToday()) {
                $depense->creerNotification($config, 'Nouvelles dépenses prevues aujourd\'hui', $depense->id);
            } elseif ($date_paiement->diffInDays($now) == $nombre_jour) {
                $message = "Dépenses à payer dans les {$nombre_jour} prochains jours";
                $depense->creerNotification($config, $message, $depense->id);
            }
        }
    }
}

