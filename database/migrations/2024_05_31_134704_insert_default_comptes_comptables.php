<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\CompteComptable;

class InsertDefaultComptesComptables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $defaultComptes = [
            ['nom_compte_comptable' => 'Clients divers', 'code_compte_comptable' => '411000'],
            ['nom_compte_comptable' => 'Ventes de marchandises', 'code_compte_comptable' => '707000'],
            ['nom_compte_comptable' => 'Prestations de services', 'code_compte_comptable' => '706000'],
            ['nom_compte_comptable' => 'Fournisseurs divers', 'code_compte_comptable' => '401000'],
            ['nom_compte_comptable' => 'Achats non stockés de matière et fournitures', 'code_compte_comptable' => '606000'],
            ['nom_compte_comptable' => 'TVA collectée', 'code_compte_comptable' => '445700'],
            ['nom_compte_comptable' => 'TVA déductible', 'code_compte_comptable' => '445660'],
        ];

        foreach ($defaultComptes as $compte) {
            CompteComptable::create($compte);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        CompteComptable::whereIn('code_compte_comptable', [
            '411000', '707000', '706000', '401000', '606000', '445700', '445660'
        ])->delete(); 
    }
}
