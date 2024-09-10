<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article_Etiquette extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'etiquette_id',
    ];
    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
    public function etiquette()
    {
        return $this->belongsTo(Etiquette::class, 'etiquette_id');
    }
}
