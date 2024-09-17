<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture #{{ $invoice->num_facture }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #3498db; color: white; } /* Couleur de fond bleu */
        h1, h2, h3, p { margin: 0; padding: 0 0 10px; }
        .total { text-align: right; font-weight: bold; }
        .header, .footer { width: 100%; }
        .header .left, .header .right { width: 48%; display: inline-block; vertical-align: top; }
        .header .left { text-align: left; }
        .header .right { text-align: right; }
        .dates { text-align: right; margin-bottom: 20px; } /* Alignement à droite pour les dates */
        .echeances-table { width: 50%; margin: 0 auto; } /* Réduction de la largeur du tableau des échéances à 50% */
    </style>
</head>
<body>

    <!-- Titre de la facture -->
    <h1 style="text-align: center;">Facture #{{ $invoice->num_facture }}</h1>

    <!-- Informations expéditeur et destinataire -->
    <div class="header">
        <div class="left">
            <h3>Expéditeur</h3>
            <p>Nom : {{ $invoice->user->name }}</p>
            <p>Email : {{ $invoice->user->email }}</p>
            <p>Téléphone : {{ $invoice->user->tel_entreprise ?? 'N/A' }}</p>
        </div>
        <div class="right">
            <h3>Destinataire</h3>
            <p>Nom : {{ $invoice->client->prenom_client.' '.$invoice->client->nom_client }}</p>
            <p>Email : {{ $invoice->client->email_client }}</p>
            <p>Téléphone : {{ $invoice->client->tel_client }}</p>
        </div>
    </div>

    <br><br>
    <!-- Dates de la facture et d'échéance alignées à droite -->
    <div class="dates">
        <p>Date de la Facture : {{ \Carbon\Carbon::parse($invoice->created_at)->format('d/m/Y') }}</p>

        @if ($invoice->type_paiement == 'echeance')
            <p>Date d'échéance : {{ \Carbon\Carbon::parse($invoice->echeances->first()->date_pay_echeance)->format('d/m/Y') }}</p>
        @endif
    </div>

    <!-- Détails des articles -->
    <h3>Détails des Articles</h3>
    <table>
        <thead>
            <tr>
                <th>Nom de l'Article</th>
                <th>Quantité</th>
                <th>Prix Unitaire</th>
                <th>Prix Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->articles as $article)
            <tr>
                <td>{{ $article->article->nom_article }}</td>
                <td>{{ $article->quantite_article }}</td>
                <td>{{ number_format($article->article->prix_unitaire, 2) }} fcfa</td>
                <td>{{ number_format($article->prix_total_article, 2) }} fcfa</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Montant total -->
    <h3 class="total">Montant Total : {{ number_format($invoice->prix_TTC, 2) }} fcfa</h3>

    <!-- Échéances s'il y en a -->
    @if ($invoice->type_paiement == 'echeance')
    <h3>Échéances</h3> 
    <table class="echeances-table"> <!-- Ajout de la classe "echeances-table" -->
        <thead>
            <tr>
                <th>Date d'Échéance</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->echeances as $echeance)
            <tr>
                <td>{{ \Carbon\Carbon::parse($echeance['date_pay_echeance'])->format('d/m/Y') }}</td>
                <td>{{ number_format($echeance['montant_echeance'], 2) }} fcfa</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

</body>
</html>
