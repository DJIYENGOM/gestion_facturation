<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Devi extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'num_devi',
        'date_devi', 
        'date_limite', 
        'prix_HT',
        'prix_TTC',
        'note_devi',
        'reduction_devi',
        'statut_devi',
        'archiver',
        'client_id',
        'sousUtilisateur_id',
        'user_id',
        'id_comptable',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Relation avec les articles de la facture
    public function articles()
    {
        return $this->hasMany(ArticleDevi::class, 'id_devi');
    }
    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function echeances()
    {
        return $this->hasMany(Echeance::class);
    }

    public function factureAccompts()
    {
        return $this->hasMany(FactureAccompt::class);
    }

    public function paiement()
    {
        return $this->belongsTo(Payement::class, 'id_paiement');
    }
    public function compteComptable()
    {
        return $this->belongsTo(CompteComptable::class, 'id_comptable');
    }

    public function Etiquettes()
    {
        return $this->hasMany(Facture_Etiquette::class, 'devi_id');
    }


    public static function creerNotification($config, $message, $devi)
    {
        $notification = new MessageNotification();
        $notification->message = $message;
        $notification->user_id = $config->user_id;
        $notification->sousUtilisateur_id = $config->sousUtilisateur_id;
        $notification->devis_id = $devi->id;
        $notification->save();

        // $this->info('Notification créée: ' . $message);
    
    }

    public static function envoyerNotificationSDeviExpirer($devi)
    {
        $config = Notification::where('user_id', $devi->user_id)
            ->orWhere('sousUtilisateur_id', $devi->sousUtilisateur_id)
            ->first();

        if ($config && $config->payement_attente && $config->nombre_jourNotif_devi >= 1) {
            $nombre_jour = $config->nombre_jourNotif_devi;

            $now = Carbon::now();
            $date_paiement = Carbon::parse($devi->date_limite);

            if ($date_paiement->isToday()) {
                $devi->creerNotification($config, 'Devis prevue aujourd\'hui', $devi);
            } elseif ($date_paiement->diffInDays($now) == $nombre_jour) {
                $message = "Devis à payer dans les {$nombre_jour} prochains jours";
                $devi->creerNotification($config, $message, $devi);
            }
        }
    }
}
