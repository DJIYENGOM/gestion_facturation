<?php

namespace App\Imports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class ClientsImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $user_id;
    protected $sousUtilisateur_id;
    protected $id_comptable;
    protected $numClient;

    public function __construct($user_id, $sousUtilisateur_id, $id_comptable, $numClient)
    {
        $this->user_id = $user_id;
        $this->sousUtilisateur_id = $sousUtilisateur_id;
        $this->id_comptable = $id_comptable;
        $this->numClient = $numClient;
    }

    public function model(array $row)
    {
       // Log::info('Importing client:', $row);

        return new Client([
            'num_client' => $this->numClient,
            'nom_client' => $row['nom'] ?? null,
            'prenom_client' => $row['prenom'] ?? null,
            'nom_entreprise' => $row['nom_entreprise'] ?? null,
            'adress_client' => $row['adress_client'] ?? null, // Mise à jour de la clé
            'email_client' => $row['email_client'] ?? null,
            'tel_client' => $row['tel_client'] ?? null,
            'type_client' => $row['type_client'] ?? null,
            'statut_client' => $row['statut_client'] ?? null,
            'num_id_fiscal' => $row['num_id_fiscal'] ?? null,
            'code_postal_client' => $row['adresse_code_postal'] ?? null, // Mise à jour de la clé
            'ville_client' => $row['ville_client'] ?? null,
            'pays_client' => $row['pays_client'] ?? null,
            'noteInterne_client' => $row['noteinterne_client'] ?? null,
            'nom_destinataire' => $row['nom_destinataire'] ?? null,
            'pays_livraison' => $row['pays_livraison'] ?? null,
            'ville_livraison' => $row['ville_livraison'] ?? null,
            'code_postal_livraison' => $row['code_postal_livraison'] ?? null,
            'tel_destinataire' => $row['tel_destinataire'] ?? null,
            'email_destinataire' => $row['email_destinataire'] ?? null,
            'infoSupplemnt' => $row['infoSupplemnt'] ?? null,
            'sousUtilisateur_id' => $this->sousUtilisateur_id,
            'user_id' => $this->user_id,
            'categorie_id' => null,
            'id_comptable' => $this->id_comptable,
        ]);
    }

    public function rules(): array
    {
        return [
            'email_client' => 'required|email',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'email_client.required' => 'L\'email est obligatoire.',
            'email_client.email' => 'L\'email doit être valide.',
        ];
    }
}
