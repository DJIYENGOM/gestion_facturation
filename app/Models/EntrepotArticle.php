<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntrepotArticle extends Model
{
    use HasFactory;

    protected $fillable = ['article_id', 'entrepot_id', 'quantiteArt_entrepot'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function entrepot()
    {
        return $this->belongsTo(Entrepot::class);
    }
}

