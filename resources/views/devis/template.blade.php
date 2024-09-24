<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>devi #{{ $devi->num_devi }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #3498db; color: white; } 
        h1, h2, h3, p { margin: 0; padding: 0 0 10px; }
        .total { text-align: right; font-weight: bold; }
        .header, .footer { width: 100%; }
        .header .left, .header .right { width: 48%; display: inline-block; vertical-align: top; }
        .header .left { text-align: left; }
        .header .right { text-align: right; }
        .dates { text-align: right; margin-bottom: 20px; } 
        .echeances-table { width: 50%; margin: 0 auto; } 
    </style>
</head>
<body>

    <h1 style="text-align: center;">Devis #{{ $devi->num_devi }}</h1>

    <div class="header">
        <div class="left">
            <h3>Expéditeur</h3>
            <p>Nom : {{ $devi->user->name }}</p>
            <p>Email : {{ $devi->user->email }}</p>
            <p>Téléphone : {{ $devi->user->tel_entreprise ?? 'N/A' }}</p>
        </div>
        <div class="right">
            <h3>Destinataire</h3>
            <p>Nom : {{ $devi->client->prenom_client.' '.$devi->client->nom_client }}</p>
            <p>Email : {{ $devi->client->email_client }}</p>
            <p>Téléphone : {{ $devi->client->tel_client }}</p>
        </div>
    </div>

    <br><br>
    <div class="dates">
        <p>Date de la devi : {{ \Carbon\Carbon::parse($devi->created_at)->format('d/m/Y') }}</p>

        @if ($devi->echeances && count($devi->echeances) > 0)
            <p>Date d'échéance : {{ \Carbon\Carbon::parse($devi->echeances->first()->date_pay_echeance)->format('d/m/Y') }}</p>
        @endif
    </div>

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
            @foreach ($devi->articles as $article)
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
    <h3 class="total">Montant Total : {{ number_format($devi->prix_TTC, 2) }} fcfa</h3>

    <!-- Échéances s'il y en a -->
    @if ($devi->echeances && count($devi->echeances) > 0)
    <h3>Échéances</h3> 
    <table class="echeances-table"> <!-- Ajout de la classe "echeances-table" -->
        <thead>
            <tr>
                <th>Date d'Échéance</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($devi->echeances as $echeance)
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
