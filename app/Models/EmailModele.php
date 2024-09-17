<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailModele extends Model
{
    use HasFactory;

    protected $fillable = ['type_modele', 'object','contenu', 'sousUtilisateur_id', 'user_id'];

    public function attachments()
    {
        return $this->hasMany(EmailAttachment::class, 'email_modele_id');
    }
}
