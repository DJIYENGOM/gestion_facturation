<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;


//class Sous_Utilisateur extends Model
class Sous_Utilisateur extends Authenticatable implements JWTSubject

{
    use HasFactory, HasApiTokens;


    protected $fillable = [
        'nom', 'prenom', 'email', 'password', 'archiver','role', 'id_user',
    ];

    // Définir la relation entre Sous_Utilisateur et son rôle
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Définir la relation entre le Sous_Utilisateur et son propriétaire
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
    public function articles()
    {
        return $this->hasMany(Article::class, 'sousUtilisateur_id');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'sousUtilisateur_id');
    }


    public function promo()
    {
        return $this->hasMany(Promo::class, 'sousUtilisateur_id');
    }

    
    public function categories()
    {
        return $this->hasMany(CategorieClient::class, 'sousUtilisateur_id');
    }

    public function payment()
    {
        return $this->hasMany(Payement::class, 'sousUtilisateur_id');
    }
    public function Entrepot()
    {
        return $this->hasMany(Entrepot::class, 'sousUtilisateur_id');
    }

    public function categorie_article()
    {
        return $this->hasMany(CategorieArticle::class, 'sousUtilisateur_id');
    }

    public function notejustificatives()
    {
        return $this->hasMany(NoteJustificative::class, 'sousUtilisateur_id');
    }
    
    public function CompteComptable()
    {
        return $this->hasMany(CompteComptable::class, 'sousUtilisateur_id');
    }

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
}
