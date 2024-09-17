<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelEmailVariable extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_modele_id', 'variable_email_id'
    ];
}
