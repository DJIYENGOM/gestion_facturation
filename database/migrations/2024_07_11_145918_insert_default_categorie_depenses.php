<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\CategorieDepense;

class InsertDefaultCategorieDepenses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Liste des catégories de dépenses par défaut
        $defaultCategories = [
            'Immobilier',
            'Automobile',
            'Salaire',
            'Impôts',
            'Assurance',
            'Maintenance',
            'Santé',
            'Transport',
            'Autre service',

        ];

        foreach ($defaultCategories as $categorie) {
            CategorieDepense::create([
                'nom_categorie_depense' => $categorie,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('categorie_depenses')->whereIn('nom_categorie_depense', [
            'Immobilier', 'Automobile', 'Salaire', 'Impôts', 'Assurance', 'Maintenance', 'Santé', 'Transport', 'Autre service'
        ])->delete();
    }
}
