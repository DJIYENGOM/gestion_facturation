<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'nom_entreprise',
        'description_entreprise',
        'logo',
        'adress_entreprise',
        'tel_entreprise',
        'devise',
        'langue',
        'signature',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }


    public function roles()
    {
        return $this->hasMany(Role::class, 'id_user');
    }

    public function Sous_utilisateur()
    {
        return $this->hasMany(Sous_Utilisateur::class, 'id_user');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'user_id');
    }

    public function promotion()
    {
        return $this->hasMany(Promo::class, 'user_id');
    }   

    public function categories()
    {
        return $this->hasMany(CategorieClient::class, 'user_id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'user_id');
    }

    public function categorie_articles()
    {
        return $this->hasMany(CategorieArticle::class, 'user_id');
    }

    public function notejustificatives()
    {
        return $this->hasMany(NoteJustificative::class, 'user_id');
    }

    public function payements()
    {
        return $this->hasMany(Payement::class, 'user_id');
    }

    public function Entrepot()
    {
        return $this->hasMany(Entrepot::class, 'user_id');
    }

    public function GrilleTarifaire()
    {
        return $this->hasMany(GrilleTarifaire::class, 'user_id');
    }

    public function echeances()
    {
        return $this->hasMany(Echeance::class, 'user_id');
    }

    public function factureAccompts()
    {
        return $this->hasMany(FactureAccompt::class, 'user_id');
    }

    public function PaiementRecu()
    {
        return $this->hasMany(PaiementRecu::class, 'user_id');
    }
    protected static function booted()
    {
        static::created(function ($user) {
            $defaultComptes = CompteComptable::whereNull('user_id')->get();

            foreach ($defaultComptes as $defaultCompte) {
                CompteComptable::create([
                    'nom_compte_comptable' => $defaultCompte->nom_compte_comptable,
                    'code_compte_comptable' => $defaultCompte->code_compte_comptable,
                    'user_id' => $user->id,
                ]);
            }
        });
    }

    public function createDefaultEmailModels(User $user)
{
    $emailTemplates = [
        'facture' => [
            'object' => 'Facture {VENTE_NUMERO} du {VENTE_DATE}',
            'contenu' => "Bonjour {DESTINATAIRE},\n\nVeuillez trouver ci-joint la Facture N° {VENTE_NUMERO} du {VENTE_DATE} pour un montant de {VENTE_PRIX_TOTAL}.\n\nNous vous remercions de votre confiance.\n\nÀ bientôt !\n\nCordialement,\n\n{ENTREPRISE}"
        ],
        'devi' => [
            'object' => 'Devis {DEVIS_NUMERO} réalisé le {DEVIS_DATE}',
            'contenu' => "Bonjour {DESTINATAIRE},\n\nVeuillez trouver ci-joint le Devis N° {DEVIS_NUMERO} réalisé le {DEVIS_DATE}.\n\nMontant total du devis : {DEVIS_PRIX_TOTAL}.\n\nSi vous avez besoin d'informations supplémentaires, n'hésitez pas à nous contacter.\n\nÀ bientôt !\n\nCordialement,\n\n{ENTREPRISE}"
        ],
        'resumer_vente' => [
            'object' => 'Résumé de vente du {VENTE_DATE}',
            'contenu' => "Bonjour {DESTINATAIRE},\n\nVeuillez trouver ci-joint le résumé de la Vente N° {VENTE_NUMERO} du {VENTE_DATE} pour un montant de {VENTE_PRIX_TOTAL}.\n\n Nous vous remercions de votre confiance.\n\nÀ bientôt !\n\nCordialement,\n\n{ENTREPRISE}"
        ],
        'recu_paiement' => [
            'object' => 'Reçu de paiement {PAIEMENT_NUMERO} du {PAIEMENT_DATE}',
            'contenu' => "Bonjour {DESTINATAIRE},\n\nVeuillez trouver ci-joint votre reçu de paiement N° {PAIEMENT_NUMERO} du {PAIEMENT_DATE}.\n\nMontant payé : {PAIEMENT_MONTANT}.\n\nCordialement,\n\n{ENTREPRISE}"
        ],
        'relanceAvant_echeance' => [
            'object' => 'Relance avant échéance pour la Facture N° {VENTE_NUMERO}',
            'contenu' => "Bonjour {DESTINATAIRE},\n\nNous vous rappelons que votre facture N° {VENTE_NUMERO} arrivera à échéance le {ECHEANCE_DATE}.\n\nMontant dû : {ECHEANCE_MONTANT}.\n\nMerci de procéder au règlement avant cette date.\n\nCordialement,\n\n{ENTREPRISE}"
        ],
        'relanceApres_echeance' => [
            'object' => 'Relance après échéance pour la Facture N° {VENTE_NUMERO}',
            'contenu' => "Bonjour {DESTINATAIRE},\n\nNous vous informons que la facture N° {VENTE_NUMERO} est échue depuis le {ECHEANCE_DATE}.\n\nMontant dû : {ECHEANCE_MONTANT}.\n\nMerci de régulariser cette situation au plus vite.\n\nCordialement,\n\n{ENTREPRISE}"
        ],
        'commande_vente' => [
            'object' => 'Confirmation de commande N° {COMMANDE_NUMERO} du {COMMANDE_DATE}',
            'contenu' => "Bonjour {DESTINATAIRE},\n\nVotre commande N° {COMMANDE_NUMERO} a bien été enregistrée le {COMMANDE_DATE}.\n\nMontant total : {COMMANDE_MONTANT}.\n\nCordialement,\n\n{ENTREPRISE}"
        ],
        'livraison' => [
            'object' => 'Livraison N° {LIVRAISON_NUMERO} effectuée le {LIVRAISON_DATE}',
            'contenu' => "Bonjour {DESTINATAIRE},\n\n Veuillez trouver ci-joint le Bon de Livraison N° {LIVRAISON_NUMERO}.\n\nLivraison prévue le : {LIVRAISON_DATE}.\n\nMerci de nous avoir fait confiance.\n\nCordialement,\n\n{ENTREPRISE}"
        ],
        'fournisseur' => [
            'object' => 'Bon de commande N° {BON_NUMERO} du {BON_DATE}',
            'contenu' => "Bonjour {DESTINATAIRE},\n\nVoici le bon de commande N° {BON_NUMERO} daté du {BON_DATE}.\n\nCordialement,\n\n{ENTREPRISE}"
        ],
    ];

    foreach ($emailTemplates as $type => $template) {
        EmailModele::create([
            'type_modele' => $type,
            'object' => $template['object'],
            'contenu' => $template['contenu'],
            'user_id' => $user->id,
            'sousUtilisateur_id' => null,
        ]);
    }
}

}
