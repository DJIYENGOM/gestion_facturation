<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleBonCommande extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_BonCommande' ,
        'id_article' , 
        'reduction_article', 
        'TVA_article',
        'prix_unitaire_article',
        'quantite_article',
        'prix_total_article',
        'prix_total_tva_article'
    ];

    public function BonCommande()
    {
        return $this->belongsTo(BonCommande::class, 'id_BonCommande');
    }

    // Relation avec l'article
    public function article()
    {
        return $this->belongsTo(Article::class, 'id_article');
    }
}
