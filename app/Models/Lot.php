<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    use HasFactory;

    protected $fillable = ['article_id', 'nomLot', 'quantiteLot'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
