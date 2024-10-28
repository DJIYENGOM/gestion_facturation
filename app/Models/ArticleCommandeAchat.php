<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleCommandeAchat extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_CommandeAchat' ,
        'id_article' , 
        'reduction_article', 
        'TVA_article',
        'prix_unitaire_article_ht',
        'prix_unitaire_article_ttc',
        'quantite_article',
        'prix_total_article',
        'prix_total_tva_article'
    ];

    public function CommandeAchat()
    {
        return $this->belongsTo(CommandeAchat::class, 'id_CommandeAchat');
    }

    // Relation avec l'article
    public function article()
    {
        return $this->belongsTo(Article::class, 'id_article');
    }
}
