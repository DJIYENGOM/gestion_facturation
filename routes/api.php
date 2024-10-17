
<?php
use App\Models\Stock;
use App\Models\BonCommande;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TvaController;
use App\Http\Controllers\DeviController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\SoldeController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\DepenseController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\EcheanceController;
use App\Http\Controllers\EntrepotController;
use App\Http\Controllers\PayementController;
use App\Http\Controllers\EtiquetteController;
use App\Http\Controllers\LivraisonController;
use App\Http\Controllers\HistoriqueController;
use App\Http\Controllers\BonCommandeController;
use App\Http\Controllers\EmailModeleController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FactureAvoirController;
use App\Http\Controllers\JournalVenteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaiementRecuController;
use App\Http\Controllers\CommandeAchatController;
use App\Http\Controllers\ModelDocumentController;
use App\Http\Controllers\FactureAccomptController;
use App\Http\Controllers\CategorieClientController;
use App\Http\Controllers\CompteComptableController;
use App\Http\Controllers\GrilleTarifaireController;
use App\Http\Controllers\SousUtilisateurController;
use App\Http\Controllers\CategorieArticleController;
use App\Http\Controllers\CategorieDepenseController;
use App\Http\Controllers\FactureRecurrenteController;
use App\Http\Controllers\NoteJustificativeController;
use App\Http\Controllers\Info_SupplementaireController;
use App\Http\Controllers\NumeroConfigurationController;
use App\Http\Controllers\ConfigurationRelanceAutoController;

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::post('login_sousUtilisateur', 'login_sousUtilisateur');
    Route::post('logout_sousUtilisateur', 'logout_sousUtilisateur')->withoutMiddleware('auth:api');
    Route::post('refresh_sousUtilisateur', 'refresh_sousUtilisateur')->withoutMiddleware('auth:api'); //->withoutMiddleware('auth:api') permet de ne pas s'ecouter sur la table user;
    Route::get('listerUser', 'listerUser');
    Route::post('modifierMotDePasse', 'modifierMotDePasse');
});


Route::controller(Info_SupplementaireController::class)->group(function () {
    Route::post('completerInfoEntreprise', 'completerInfoEntreprise');
    Route::get('afficherInfoEntreprise', 'afficherInfoEntreprise');
    Route::post('modifierInfoEntreprise', 'completerInfoEntreprise');
    
});

Route::controller(RoleController::class)->group(function () {
    Route::post('ajouterRole', 'ajouterRole');
    Route::get('listerRole', 'listerRole');
    Route::post('modifierRole/{id}', 'modifierRole');
    Route::delete('supprimerRole/{id}', 'supprimerRole');
});


Route::controller(SousUtilisateurController::class)->group(function () {
    Route::post('ajouterUtilisateur', 'ajouterSousUtilisateur');
    Route::get('listeUtilisateurNonArchive', 'listeUtilisateurNonArchive');
    Route::get('listeUtilisateurArchive', 'listeUtilisateurArchive');
    Route::post('modifierSousUtilisateur/{id}', 'modifierSousUtilisateur');
    Route::post('ArchiverSousUtilisateur/{id}', 'ArchiverSousUtilisateur');
    Route::post('Des_ArchiverSousUtilisateur/{id}', 'DesArchiverSousUtilisateur');
});


Route::controller(PromoController::class)->group(function () {
    Route::post('ajouterPromo', 'ajouterPromo');
    Route::get('listerPromo', 'listerPromo');
    Route::post('modifierPromo/{id}', 'modifierPromo');
    Route::post('supprimerPromo/{id}', 'supprimerPromo');
});

Route::post('importArticle', [ArticleController::class, 'importArticle']);
Route::get('exportArticles', [ArticleController::class, 'exportArticles']);

Route::get('exportServices', [ArticleController::class, 'exportServices']);

Route::post('ajouterArticle', [ArticleController::class, 'ajouterArticle']);
Route::get('listerArticles', [ArticleController::class, 'listerArticles']);
Route::post('modifierArticle/{id}', [ArticleController::class, 'modifierArticle']);
Route::delete('supprimerArticle/{id}', [ArticleController::class, 'supprimerArticle']);
Route::post('affecterPromoArticle/{id}', [ArticleController::class, 'affecterPromoArticle']);
Route::post('affecterCategorieArticle/{id}', [ArticleController::class, 'affecterCategorieArticle']);
Route::post('articles_modifier_quantite/{id}', [ArticleController::class, 'modifierQuantite']);

Route::get('listerLotsArticle/{articleId}', [ArticleController::class, 'listerLotsArticle']);
Route::get('listerAutrePrixArticle/{articleId}', [ArticleController::class, 'listerAutrePrixArticle']);
Route::get('listerEntrepotsArticle/{articleId}', [ArticleController::class, 'listerEntrepotsArticle']);
Route::get('afficherArticleAvecPrix/{articleId}', [ArticleController::class, 'afficherArticleAvecPrix']);


Route::get('listerNotes', [NoteJustificativeController::class, 'listerNotes']);
Route::post('modifierNote/{id}', [NoteJustificativeController::class, 'modifierNote']);
Route::delete('supprimerNote/{id}', [NoteJustificativeController::class, 'supprimerNote']);


Route::controller(CategorieClientController::class)->group(function () {
    Route::post('ajouterCategorie', 'ajouterCategorie');
    Route::get('listerCategorieClient', 'listerCategorieClient');
    Route::post('modifierCategorie/{id}', 'modifierCategorie');
    Route::delete('supprimerCategorie/{id}', 'supprimerCategorie');

});

Route::controller(ClientController::class)->group(function () {
    Route::post('ajouterClient', 'ajouterClient');
    Route::get('listerClients', 'listerClients');
    Route::post('modifierClient/{id}', 'modifierClient');
    Route::delete('supprimerClient/{id}', 'supprimerClient');
    Route::post('importClient', 'importClient');
    Route::get('exportClients', 'exportClients');
    Route::post('sendClientEmail/{id_facture}', 'sendClientEmail');

});

Route::controller(CategorieArticleController::class)->group(function () {
    Route::post('ajouterCategorieArticle', 'ajouterCategorie');
    Route::get('listerCategorieProduit', 'listerCategorieProduit');
    Route::get('listerCategorieService', 'listerCategorieService');
    Route::post('modifierCategorieArticle/{id}', 'modifierCategorie');
    Route::delete('supprimerCategorieArticle/{id}', 'supprimerCategorie');
});

Route::controller(PayementController::class)->group(function () {
    Route::post('ajouterPayement', 'ajouterPayement');
    Route::get('listerPayements', 'listerPayements');
    Route::post('modifierPayement/{id}', 'modifierPayement');
    Route::delete('supprimerPayement/{id}', 'supprimerPayement');
    Route::post('RapportMoyenPayement',  'RapportMoyenPayement');


});

Route::post('creerFactureAccomp', [FactureAccomptController::class, 'creerFactureAccomp']);
Route::get('listerfactureAccomptsParFacture/{id_facture}', [FactureAccomptController::class, 'listerfactureAccomptsParFacture']);

Route::post('creerFacture', [FactureController::class, 'creerFacture']);
Route::post('listeArticlesFacture/{id_facture}', [FactureController::class, 'listeArticlesFacture']);
Route::get('listerFactures', [FactureController::class, 'listerToutesFactures']);
Route::post('lireFacture/{id}', [FactureController::class, 'lireFacture']);
Route::post('validerFacture/{id}', [FactureController::class, 'validerFacture']);
Route::delete('supprimeArchiveFacture/{id}', [FactureController::class, 'supprimeArchiveFacture']);
Route::get('DetailsFacture/{num_facture}', [FactureAvoirController::class, 'DetailsFacture']);

Route::post('listerFacturesEcheance', [FactureController::class, 'listerFacturesEcheance']);
Route::post('listerFacturesAccompt', [FactureController::class, 'listerFacturesAccompt']);
Route::post('listerFacturesPayer', [FactureController::class, 'listerFacturesPayer']);

Route::get('listeFactureParClient/{clientId}', [FactureController::class, 'listeFactureParClient']);
Route::post('RapportFacture', [FactureController::class, 'RapportFacture']);
Route::post('RapportFluxTrésorerie', [FactureController::class, 'RapportFluxTrésorerie']);

Route::get('exportFactures', [FactureController::class, 'exportFactures']);



Route::post('ajouterCompteComptable', [CompteComptableController::class, 'ajouterCompteComptable']);
Route::get('listerCompteComptables', [CompteComptableController::class, 'listerCompteComptables']);
Route::post('modifierCompteComptable/{id}', [CompteComptableController::class, 'modifierCompteComptable']);
Route::delete('supprimerCompteComptable/{id}', [CompteComptableController::class, 'supprimerCompteComptable']);

Route::controller(EntrepotController::class)->group(function () {
    Route::post('ajouterEntrepot', 'ajouterEntrepot');
    Route::get('listerEntrepots', 'listerEntrepots');
    Route::post('modifierEntrepot/{id}', 'modifierEntrepot');
    Route::delete('supprimerEntrepot/{id}', 'supprimerEntrepot');

});

Route::post('creerGrilleTarifaire', [GrilleTarifaireController::class, 'creerGrilleTarifaire']);
Route::get('listerTariPourClientSurArticle/{clientId}/{articleId}', [GrilleTarifaireController::class, 'listerTariPourClientSurArticle']);
Route::post('modifierGrilleTarifaire/{idTarif}', [GrilleTarifaireController::class, 'modifierGrilleTarifaire']);
Route::delete('supprimerGrilleTarifaire/{idTarif}', [GrilleTarifaireController::class, 'supprimerGrilleTarifaire']);


Route::post('factures_echeances/{factureId}', [EcheanceController::class, 'creerEcheance']);
Route::post('listEcheanceParFacture/{factureId}', [EcheanceController::class, 'listEcheanceParFacture']);
Route::post('modifierEcheance/{echeanceId}', [EcheanceController::class, 'modifierEcheance']);
Route::delete('supprimerEcheance/{echeanceId}', [EcheanceController::class, 'supprimerEcheance']);
Route::post('transformerEcheanceEnPaiementRecu/{echeanceId}', [EcheanceController::class,'transformerEcheanceEnPaiementRecu']);

Route::post('getNombreClientsNotifApresDemain',[EcheanceController::class,'getNombreClientsNotifApresDemain']);
Route::post('getNombreClientsNotifDans7Jours',[EcheanceController::class,'getNombreClientsNotifDans7Jours']);
Route::post('getNombreClientsNotifApresEcheance',[EcheanceController::class,'getNombreClientsNotifApresEcheance']);
Route::post('getNombreClientNotifApresEcheanceDans7Jours',[EcheanceController::class,'getNombreClientNotifApresEcheanceDans7Jours']);

Route::post('RapportPaiement_enAttents', [EcheanceController::class, 'RapportPaiement_enAttents']);

Route::controller(PaiementRecuController::class)->group(function (){
    Route::post('ajouterPaiementRecu','ajouterPaiementRecu');
    Route::post('listPaiementsRecusParFacture/{factureId}', 'listPaiementsRecusParFacture');
    Route::delete('supprimerPaiementRecu/{paiementRecuId}', 'supprimerPaiementRecu');
    Route::post('transformerPaiementRecuEnEcheance/{paiementRecuId}', 'transformerPaiementRecuEnEcheance');
    Route::post('RapportPaiementRecu', 'RapportPaiementRecu');
 
});


Route::controller(DeviController::class)->group(function(){
    Route::post('creerDevi','creerDevi');
    Route::post('TransformeDeviEnFacture/{deviId}','TransformeDeviEnFacture');
    Route::post('TransformeDeviEnBonCommande/{deviId}','TransformeDeviEnBonCommande');
    Route::get('listerToutesDevi','listerToutesDevi');
    Route::post('supprimerDevi/{id}','supprimerDevi');
    Route::post('annulerDevi/{deviId}','annulerDevi');  
    Route::post('DetailsDevis/{id}','DetailsDevis'); 
    Route::get('exporterDevis','exporterDevis'); 
    Route::get('listeDeviParClient/{clientId}','listeDeviParClient');
});

Route::controller(BonCommandeController::class)->group(function(){
    Route::post('creerBonCommande','creerBonCommande');
    Route::post('TransformeBonCommandeEnFacture/{id}','TransformeBonCommandeEnFacture');
    Route::get('listerTousBonCommande','listerTousBonCommande');
    Route::get('listeBonCommandeParClient/{clientId}','listeBonCommandeParClient');
    Route::post('supprimerBonCommande/{id}','supprimerBonCommande');
    Route::post('annulerBonCommande/{id}','annulerBonCommande');
    Route::post('DetailsBonCommande/{id}','DetailsBonCommande');  
    Route::get('exporterBonCommandes','exporterBonCommandes');  
    Route::post('RapportCommandeVente','RapportCommandeVente');
});

Route::controller(FournisseurController::class)->group(function(){
    Route::post('ajouterFournisseur','ajouterFournisseur');
    Route::get('listerTousFournisseurs','listerTousFournisseurs');
    Route::post('modifierFournisseur/{id}','modifierFournisseur');
    Route::delete('supprimerFournisseur/{id}','supprimerFournisseur');
});

Route::controller(LivraisonController::class)->group(function(){
    Route::post('ajouterLivraison','ajouterLivraison');
    Route::get('listerToutesLivraisons','listerToutesLivraisons');
    Route::post('LivraisonPreparer/{id}','LivraisonPreparer');
    Route::delete('supprimerLivraison/{id}','supprimerLivraison');
    Route::post('PlanifierLivraison/{id}','PlanifierLivraison');
    Route::post('transformerLivraisonEnFacture/{id}','transformerLivraisonEnFacture');
    Route::post('RealiserLivraison/{id}','RealiserLivraison');
    Route::post('DetailsLivraison/{id}','DetailsLivraison');
    Route::get('exporterLivraison','exporterLivraisons');
    Route::get('listerToutesLivraisonsParClient/{clientId}','listerToutesLivraisonsParClient');
    Route::post('RapportLivraison','RapportLivraison');
});

Route::post('configurerNumeros',[NumeroConfigurationController::class, 'configurerNumeros']);
Route::get('InfoConfigurationFacture',[NumeroConfigurationController::class, 'InfoConfigurationFacture']);
Route::get('InfoConfigurationDevis',[NumeroConfigurationController::class, 'InfoConfigurationDevis']);
Route::get('InfoConfigurationLivraison',[NumeroConfigurationController::class, 'InfoConfigurationLivraison']);
Route::get('InfoConfigurationBonCommande',[NumeroConfigurationController::class, 'InfoConfigurationBonCommande']);
Route::get('InfoConfigurationDepense',[NumeroConfigurationController::class, 'InfoConfigurationDepense']);
Route::get('InfoConfigurationFournisseur',[NumeroConfigurationController::class, 'InfoConfigurationFournisseur']);
Route::get('InfoConfigurationCommandeAchat',[NumeroConfigurationController::class, 'InfoConfigurationCommandeAchat']);

Route::controller(FactureAvoirController::class)->group(function(){
    Route::post('creerFactureAvoir','creerFactureAvoir');
    Route::get('listerToutesFacturesAvoirs','listerToutesFacturesAvoirs');
    Route::get('listerToutesFacturesSimpleAvoir','listerToutesFacturesSimpleAvoir');
    Route::delete('supprimerFacture/{num_facture}','supprimerFacture');
    
});

Route::post('creerFactureRecurrente',[FactureRecurrenteController::class, 'creerFactureRecurrente']);
Route::get('listerToutesFacturesRecurrentes',[FactureRecurrenteController::class, 'listerToutesFacturesRecurrentes']);
Route::post('supprimerFactureRecurrente/{id}',[FactureRecurrenteController::class, 'supprimerFactureRecurrente']);

Route::post('listeSoldeParClient/{clientId}',[SoldeController::class, 'listeSoldeParClient']);
Route::post('supprimer_archiverSolde/{id}',[SoldeController::class, 'supprimer_archiverSolde']);
Route::post('ajouterSolde/{clientId}',[SoldeController::class, 'ajouterSolde']);

Route::post('ajouterCategorieDepense',[CategorieDepenseController::class, 'ajouterCategorieDepense']);
Route::get('listerCategorieDepense',[CategorieDepenseController::class, 'listerCategorieDepense']);
Route::delete('supprimerCategorieDepense/{id}',[CategorieDepenseController::class, 'supprimerCategorieDepense']);

Route::post('creerDepense',[DepenseController::class, 'creerDepense']);
Route::get('listerDepenses',[DepenseController::class, 'listerDepenses']);
Route::post('modifierDepense/{id}',[DepenseController::class, 'modifierDepense']);
Route::post('PayerDepense/{id}',[DepenseController::class, 'PayerDepense']);
Route::delete('supprimerDepense/{id}',[DepenseController::class, 'supprimerDepense']);
Route::get('exporterDepenses',[DepenseController::class, 'exporterDepenses']);
Route::post('RapportDepense',[DepenseController::class, 'RapportDepense']);

Route::post('creerCommandeAchat',[CommandeAchatController::class, 'creerCommandeAchat']);
Route::get('listerToutesCommandesAchat',[CommandeAchatController::class, 'listerToutesCommandesAchat']);
Route::get('afficherDetailCommandeAchat/{id}',[CommandeAchatController::class, 'afficherDetailCommandeAchat']);
Route::post('modifierCommandeAchat/{id}',[CommandeAchatController::class, 'modifierCommandeAchat']);
Route::delete('supprimerCommandeAchat/{id}',[CommandeAchatController::class, 'supprimerCommandeAchat']);
Route::post('annulerCommandeAchat/{id}',[CommandeAchatController::class, 'annulerCommandeAchat']);
Route::get('exporterCommandesAchats',[CommandeAchatController::class, 'exporterCommandesAchats']);


Route::post('ArreteCreationAutomatiqueFactureRecurrente',[FactureController::class, 'ArreteCreationAutomatiqueFactureRecurrente']);

Route::controller(HistoriqueController::class)->group(function(){

    Route::get('listerMessagesHistoriqueAujourdhui','listerMessagesHistoriqueAujourdhui');
    Route::post('supprimerHistorique/{id}','supprimerHistorique');
});

Route::controller(NotificationController::class)->group(function(){
    Route::post('configurerNotification','configurerNotification');
    Route::get('listerConfigurationNotification','listerConfigurationNotification');
    Route::get('listerNotifications','listerNotifications');
    Route::post('supprimeNotificationParType','supprimeNotificationParType');
});

Route::controller(StockController::class)->group(function(){
    Route::get('listerStocks','listerStocks');
    Route::get('ListeStock_a_modifier','ListeStock_a_modifier');
    Route::post('modifierStock','modifierStock');
});

Route::get ('InfoSurTva_Recolte_Deductif_Reverse', [TvaController::class, 'InfoSurTva_Recolte_Deductif_Reverse']);
Route::post('RapportTVA', [TvaController::class, 'RapportTVA']);

Route::controller(ConversationController::class)->group(function(){
    Route::get('listerConversations','listerConversations');
    Route::post('ajouterConversation','ajouterConversation');
    Route::post('supprimerConversation/{id}','supprimerConversation');
    Route::post('modifierConversation/{id}','modifierConversation');
    Route::get('detailConversation/{id}','detailConversation');
    Route::get('listerConversationsParClient/{clientId}','listerConversationsParClient');
});

Route::post('creerEtiquette',[EtiquetteController::class, 'creerEtiquette']);
Route::get('ListerEtiquette',[EtiquetteController::class, 'ListerEtiquette']);
Route::post('modifierEtiquette/{id}',[EtiquetteController::class, 'modifierEtiquette']);
Route::post('supprimerEtiquette/{id}',[EtiquetteController::class, 'supprimerEtiquette']);  
  

Route::post('createEmailModele',[EmailModeleController::class, 'createEmailModele']); 

Route::post('envoyerEmailFacture/{id_facture}',[EmailModeleController::class, 'envoyerEmailFacture']);
Route::post('DetailEmailFacture_genererPDF/{id_facture}', [EmailModeleController::class, 'DetailEmailFacture_genererPDF']);

Route::post('DetailEmailDevi_genererPDF/{id_devi}', [EmailModeleController::class, 'DetailEmailDevi_genererPDF']);
Route::post('envoyerEmailDevi/{id_devi}', [EmailModeleController::class, 'envoyerEmailDevi']);

Route::post('DetailEmailBonCommande_genererPDF/{id_bonCommande}', [EmailModeleController::class, 'DetailEmailBonCommande_genererPDF']);
Route::post('envoyerEmailBonCommande/{id_bonCommande}', [EmailModeleController::class, 'envoyerEmailBonCommande']);

Route::post('DetailEmailLivraison_genererPDF/{id_livraison}', [EmailModeleController::class, 'DetailEmailLivraison_genererPDF']);
Route::post('envoyerEmailLivraison/{id_livraison}', [EmailModeleController::class, 'envoyerEmailLivraison']);

Route::post('DetailEmailCommandeAchat_genererPDF/{id_CommandeAchat}', [EmailModeleController::class, 'DetailEmailCommandeAchat_genererPDF']);
Route::post('envoyerEmailCommandeAchat/{id_CommandeAchat}', [EmailModeleController::class, 'envoyerEmailCommandeAchat']);

Route::post('DetailEmailResumeVente_genererPDF/{id_facture}', [EmailModeleController::class, 'DetailEmailResumeVente_genererPDF']);
Route::post('envoyerEmailResumeVente/{id_facture}', [EmailModeleController::class, 'envoyerEmailResumeVente']);

Route::post('DetailEmailPaiementRecu_genererPDF/{id_PaiementRecu}', [EmailModeleController::class, 'DetailEmailPaiementRecu_genererPDF']);
Route::post('envoyerEmailPaiementRecu/{id_PaiementRecu}', [EmailModeleController::class, 'envoyerEmailPaiementRecu']);

Route::post('DetailEmailRelanceAvantEcheance_genererPDF/{id_echeance}', [EmailModeleController::class, 'DetailEmailRelanceAvantEcheance_genererPDF']);
Route::post('envoyerEmailRelanceAvantEcheance/{id_echeance}', [EmailModeleController::class, 'envoyerEmailRelanceAvantEcheance']);

Route::post('DetailEmailRelanceApresEcheance_genererPDF/{id_echeance}', [EmailModeleController::class, 'DetailEmailRelanceApresEcheance_genererPDF']);
Route::post('envoyerEmailRelanceApresEcheance/{id_echeance}', [EmailModeleController::class, 'envoyerEmailRelanceApresEcheance']);

Route::post('ConfigurerRelanceAuto', [ConfigurationRelanceAutoController::class, 'ConfigurerRelanceAuto']);
Route::get('listerConfigurationRelance', [ConfigurationRelanceAutoController::class, 'listerConfigurationRelance']);

Route::post('CreerModelDocument', [ModelDocumentController::class, 'CreerModelDocument']);
Route::post('ModifierModelDocument/{id}', [ModelDocumentController::class, 'ModifierModelDocument']);
Route::get('listerModelesDocumentsParType/{typeDocument}', [ModelDocumentController::class, 'listerModelesDocumentsParType']);

Route::post('genererPDFDevis/{devisId}/{modelDocumentId}',[DeviController::class, 'genererPDFDevis']);
Route::post('genererPDFFacture/{factureId/{modelDocumentId}',[FactureController::class, 'genererPDFFacture']);
Route::post('genererPDFLivraison/{livraisonId}/{modelDocumentId}',[LivraisonController::class, 'genererPDFLivraison']);
Route::post('genererPDFBonCommande/{bonCommandeId}/{modelDocumentId}',[BonCommandeController::class, 'genererPDFBonCommande']);
Route::post('genererPDFCommandeAchat/{commandeAchatId}/{modelDocumentId}',[CommandeAchatController::class, 'genererPDFCommandeAchat']);

Route::get('getJournalVentesEntreDates', [JournalVenteController::class, 'getJournalVentesEntreDates']);
Route::get('getJournalAchatsEntreDates', [JournalVenteController::class, 'getJournalAchatsEntreDates']);