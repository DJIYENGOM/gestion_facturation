<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientsExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Client::all();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Code',
            'Nom',
            'Prénom',
            'Nom de l\'entreprise',
            'Adresse',
            'Email',
            'Téléphone',
            'Type de client',
            'Statut du client',
            'Numéro ID fiscal',
            'Code postal',
            'Ville',
            'Pays',
            'Note interne',
            'Nom du destinataire',
            'Pays de livraison',
            'Ville de livraison',
            'Code postal de livraison',
            'Téléphone du destinataire',
            'Email du destinataire',
            'Informations supplémentaires',
            'Sous-utilisateur ID',
            'Utilisateur ID',
            'Catégorie ID',
            'ID comptable',
            'Date de création',
            'Date de mise à jour',
        ];
    }
}
