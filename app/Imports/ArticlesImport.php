<?php
namespace App\Imports;

use App\Models\Article;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class ArticlesImport implements ToModel, WithValidation, WithHeadingRow
{
    protected $user_id;
    protected $sousUtilisateur_id;

    public function __construct($user_id, $sousUtilisateur_id)
    {
        $this->user_id = $user_id;
        $this->sousUtilisateur_id = $sousUtilisateur_id;
    }

    public function model(array $row)
    {
        //Log::info('Importing article:', $row);

        return new Article([
            'num_article' => $row['code'] ?? null,
            'nom_article' => $row['libelle'] ?? null,
            'description' => $row['description'] ?? null,
            'prix_unitaire' => $row['prix_unitaire'] ?? 0,
            'quantite' => $row['quantite'] ?? 0,
            'prix_achat' => $row['prix_achat'] ?? 0,
            'benefice' => $row['benefice'] ?? 0,
            'prix_promo' => $row['prix_promo'] ?? 0,
            'prix_tva' => $row['prix_tva'] ?? 0,
            'id_categorie_article' => $row['categorie'] ?? null,
            'tva' => $row['tva'] ?? 0,
            'benefice_promo' => $row['benefice_promo'] ?? 0,
            'quantite_alert' => $row['quantite_alerte'] ?? 0,
            'type_article' => $row['type_article'] ?? null,
            'unité' => $row['unite'] ?? null,
            'promo_id' => null,
            'sousUtilisateur_id' => $this->sousUtilisateur_id,
            'user_id' => $this->user_id,
            'id_comptable' => null,
            'doc_externe' => null,
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => 'required|unique:articles,num_article',
            'prix_unitaire' => 'required|numeric',
            // Ajoutez d'autres règles de validation ici si nécessaire
        ];
    }

    public function customValidationMessages()
    {
        return [
            'code.required' => 'Le numéro d\'article est requis.',
            'code.unique' => 'Le numéro d\'article doit être unique.',
            'prix_unitaire.required' => 'Le prix unitaire est requis.',
            'prix_unitaire.numeric' => 'Le prix unitaire doit être un nombre.',
        ];
    }
}
