<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variante extends Model
{
    use HasFactory;

    
    protected $fillable = ['article_id', 'nomVariante', 'quantiteVariante'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
