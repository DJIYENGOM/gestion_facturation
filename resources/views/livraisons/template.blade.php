<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title> Livraison #{{ $livraison->num_livraison }}</title>
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

    <h1 style="text-align: center;">Livraison #{{ $livraison->num_livraison }}</h1>

    <div class="header">
        <div class="left">
            <h3>Expéditeur</h3>
            <p>Nom : {{ $livraison->user->name }}</p>
            <p>Email : {{ $livraison->user->email }}</p>
            <p>Téléphone : {{ $livraison->user->tel_entreprise ?? 'N/A' }}</p>
        </div>
        <div class="right">
            <h3>Destinataire</h3>
            <p>Nom : {{ $livraison->client->prenom_client.' '.$livraison->client->nom_client }}</p>
            <p>Email : {{ $livraison->client->email_client }}</p>
            <p>Téléphone : {{ $livraison->client->tel_client }}</p>
        </div>
    </div>

    <br><br>
    <div class="dates">
        <p>Date du livraison : {{ \Carbon\Carbon::parse($livraison->created_at)->format('d/m/Y') }}</p>
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
            @foreach ($livraison->articles as $article)
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
    <h3 class="total">Montant Total : {{ number_format($livraison->prix_TTC, 2) }} fcfa</h3>

 
</body>
</html>
