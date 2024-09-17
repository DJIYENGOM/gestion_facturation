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
}
