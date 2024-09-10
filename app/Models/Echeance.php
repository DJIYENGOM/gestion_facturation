<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Echeance extends Model
{
    use HasFactory;

    protected $fillable = [
        'facture_id',
        'date_pay_echeance',
        'montant_echeance',
        'statut_paiement',
        'commentaire',
        'devi_id',
        'bonCommande_id',
        'id_depense',
        'sousUtilisateur_id',
        'user_id',

    ];

    public function facture()
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    public function devi()
    {
        return $this->belongsTo(Devi::class, 'devi_id');
    }

    public function BonCommande()
    {
        return $this->belongsTo(BonCommande::class, 'bonCommande_id');
    }

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function creerNotification($config, $message, $echeance)
    {
        $notification = new MessageNotification();
        $notification->message = $message;
        $notification->user_id = $config->user_id;
        $notification->sousUtilisateur_id = $config->sousUtilisateur_id;
        $notification->echeance_id = $echeance->id;
        $notification->facture_id= $echeance->facture_id ?? null;
        $notification->save();

        // $this->info('Notification créée: ' . $message);
    
    }

    public static function envoyerNotificationSEcheanceImpayer($echeance)
    {
        $config = Notification::where('user_id', $echeance->user_id)
            ->orWhere('sousUtilisateur_id', $echeance->sousUtilisateur_id)
            ->first();

        if ($config && $config->payement_attente && $config->nombre_jourNotif_echeance >= 1) {
            $nombre_jour = $config->nombre_jourNotif_echeance;

            $now = Carbon::now();
            $date_paiement = Carbon::parse($echeance->date_pay_echeance);

            if ($date_paiement->isToday()) {
                $echeance->creerNotification($config, 'Echeances prevue aujourd\'hui', $echeance);
            } elseif ($date_paiement->diffInDays($now) == $nombre_jour) {
                $message = "Echeances à payer dans les {$nombre_jour} prochains jours";
                $echeance->creerNotification($config, $message, $echeance);
            }
        }
    }
}
