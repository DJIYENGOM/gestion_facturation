<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'role','id_user'
    ];

    // Définir la relation entre le rôle et les utilisateurs
    public function utilisateurs()
    {
        return $this->hasMany(Sous_Utilisateur::class, 'id_role');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
