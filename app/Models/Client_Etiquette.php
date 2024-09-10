<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client_Etiquette extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'etiquette_id',
    ];
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    public function etiquette()
    {
        return $this->belongsTo(Etiquette::class, 'etiquette_id');
    }
}
