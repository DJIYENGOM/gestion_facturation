<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteJustificative extends Model
{
    use HasFactory;

    protected $fillable = [
        'note','sousUtilisateur_id','user_id','article_id'
    ];

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
