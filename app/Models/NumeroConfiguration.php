<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumeroConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type_document',
        'type_numerotation',
        'prefixe',
        'format',
        'compteur',
    ];

    protected $casts = [
        'type_document' => 'string',
        'type_numerotation' => 'string',
        'format' => 'string',
    ];

    // Relation avec User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
