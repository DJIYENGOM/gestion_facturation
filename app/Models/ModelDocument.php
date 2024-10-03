<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelDocument extends Model
{
    use HasFactory;

    protected $fillable = [
  'reprendre_model_vente',
  'typeDesign',
  'typeDocument' ,
  'content' ,
  'signatureExpediteurModel',
    'mention_expediteur',
    'image_expediteur',
  'signatureDestinataireModel', 
    'mention_destinataire',
  'autresImagesModel', 
    'image',
  'conditionsPaiementModel', 
    'conditionPaiement',
  'coordonneesBancairesModel', 
    'titulaireCompte',
    'IBAN', 
    'BIC', 
  'notePiedPageModel', 
    'peidPage',
    'css',
    'sousUtilisateur_id',
    'user_id',
    ];


}
