<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariableEmail extends Model
{
    use HasFactory;

    protected $fillable =[
        'nom_variable'
    ];

}
