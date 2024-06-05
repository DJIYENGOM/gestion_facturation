<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Article extends Model
{
    use HasFactory;

    protected $fillable = ['nom_article','num_article','tva','prix_tva','doc_externe','unité','id_comptable', 'description', 'prix_unitaire','quantite','prix_achat','benefice', 'type_article','prix_promo','benefice_promo', 'promo_id','quantite_alert', 'sousUtilisateur_id','user_id','id_categorie_article'];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            // Générer le num_article s'il n'est pas fourni ou s'il existe déjà
            if (empty($article->num_article) || self::where('num_article', $article->num_article)->exists()) {
                $article->num_article = self::generateUniqueNumArticle();
            }
        });
    }

    private static function generateUniqueNumArticle()
    {
        $latestArticle = self::latest('id')->first();
        $nextId = $latestArticle ? $latestArticle->id + 1 : 1;
        $numArticle = str_pad($nextId, 6, '0', STR_PAD_LEFT);

        while (self::where('num_article', $numArticle)->exists()) {
            $nextId++;
            $numArticle = str_pad($nextId, 6, '0', STR_PAD_LEFT);
        }
        return $numArticle;
    }

    
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

    public function GrilleTarifaire()
    {
        return $this->hasMany(GrilleTarifaire::class);
    }
}
