<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtcleFacture extends Model
{
    use HasFactory;
    protected $fillable = [
        'id_facture' ,
        'id_artcle' , 
        'reduction_article', 
        'TVA_article',
        'prix_unitaire_article', ,
        'quantite_article',
        'prix_totalt_article'
    ];
}
