<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sous_Utilisateur extends Model
{
    use HasFactory;

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
}
