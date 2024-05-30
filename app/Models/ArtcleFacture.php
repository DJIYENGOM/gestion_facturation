<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtcleFacture extends Model
{
    use HasFactory;
    protected $fillable = [
        'id_facture' ,
        'id_article' , 
        'reduction_article', 
        'TVA_article',
        'prix_unitaire_article',
        'quantite_article',
        'prix_total_article',
        'prix_total_tva_article'
    ];

    public function facture()
    {
        return $this->belongsTo(Facture::class, 'id_facture');
    }

    // Relation avec l'article
    public function article()
    {
        return $this->belongsTo(Article::class, 'id_article');
    }
}
