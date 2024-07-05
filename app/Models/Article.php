<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_article',
        'num_article',
        'tva',
        'prix_tva',
        'doc_externe',
        'unité',
        'id_comptable', 
        'description', 
        'prix_unitaire',
        'quantite',
        'prix_achat',
        'benefice',
        'type_article',
        'prix_promo',
        'benefice_promo', 
        'promo_id',
        'quantite_alert', 
        'sousUtilisateur_id','user_id','id_categorie_article'];




    
    public function promotion()
    {
        return $this->belongsTo(Promo::class);
    }


    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function categorieArticle()
    {
        return $this->belongsTo(CategorieArticle::class, 'id_categorie_article');
    }
    public function compteComptable()
    {
        return $this->belongsTo(CompteComptable::class, 'id_comptable');
    }

    public function notejustificatives()
    {
        return $this->hasMany(NoteJustificative::class, 'article_id');
    }

    public function articlesFacture()
    {
        return $this->hasMany(ArtcleFacture::class);
    }

    public function articlesDevi()
    {
        return $this->hasMany(ArticleDevi::class);
    }

    public function articlesBonCommande()
    {
        return $this->hasMany(ArticleBonCommande::class);
    }

    public function articleLivraison()
    {
        return $this->hasMany(ArticleLivraison::class);
    }

    
    public function GrilleTarifaire()
    {
        return $this->hasMany(GrilleTarifaire::class);
    }
}
