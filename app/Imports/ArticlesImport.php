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
    protected $id_comptable;
    protected $numArticle;

    public function __construct($user_id, $sousUtilisateur_id, $id_comptable, $numArticle)
    {
        $this->user_id = $user_id;
        $this->sousUtilisateur_id = $sousUtilisateur_id;
        $this->id_comptable = $id_comptable;
        $this->numArticle = $numArticle;
    }

    public function model(array $row)
    {
        //Log::info('Importing article:', $row);

        return new Article([
            'num_article' => $this->numArticle,
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
            'id_comptable' => $this->id_comptable,
            'doc_externe' => null,
        ]);
    }

    public function rules(): array
    {
        return [
            'prix_unitaire' => 'required|numeric',
            // Ajoutez d'autres règles de validation ici si nécessaire
        ];
    }

    public function customValidationMessages()
    {
        return [
            'prix_unitaire.required' => 'Le prix unitaire est requis.',
            'prix_unitaire.numeric' => 'Le prix unitaire doit être un nombre.',
        ];
    }
}
