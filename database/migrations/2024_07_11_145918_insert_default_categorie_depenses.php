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
            'Immobilière',
            'Automobile',
            'Salaire',
            'Impôts',
            'Assurance',
            'Autre service',
            'Maintenance',
            'Santé',
            'Transport',
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
            'Immobilière', 'Automobile', 'Salaire', 'Impôts', 'Assurance', 'Autre service', 'Maintenance', 'Santé', 'Transport'
        ])->delete();
    }
}
