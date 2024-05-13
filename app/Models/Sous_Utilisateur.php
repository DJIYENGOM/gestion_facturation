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
        'nom', 'prenom', 'email', 'password', 'archiver','id_role', 'id_user',
    ];

    // Définir la relation entre Sous_Utilisateur et son rôle
    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }

    // Définir la relation entre le Sous_Utilisateur et son propriétaire
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
    public function articles()
    {
        return $this->hasMany(Article::class);
    }


    public function promotion()
    {
        return $this->hasMany(Promo::class);
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
