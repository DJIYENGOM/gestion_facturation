<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_fact',
        'date_creation', 
        'reduction_facture', 
        'montant_total_fact',
        'note_fact',
        'archiver',
        'validation',
        'statut',
        'client_id',
        'sousUtilisateur_id',
        'user_id'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Relation avec les articles de la facture
    public function articles()
    {
        return $this->hasMany(ArtcleFacture::class);
    }

    public static function generateNumFacture($id)
    {
        $year = date('Y');
        return 'F' . $year . '00' . $id;
    }
}
