
<?php
use App\Models\BonCommande;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\EcheanceController;
use App\Http\Controllers\EntrepotController;
use App\Http\Controllers\PayementController;
use App\Http\Controllers\LivraisonController;
use App\Http\Controllers\BonCommandeController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\PaiementRecuController;
use App\Http\Controllers\CategorieClientController;
use App\Http\Controllers\CompteComptableController;
use App\Http\Controllers\GrilleTarifaireController;
use App\Http\Controllers\SousUtilisateurController;
use App\Http\Controllers\CategorieArticleController;
use App\Http\Controllers\FactureAccomptController;
use App\Http\Controllers\NoteJustificativeController;
use App\Http\Controllers\Info_SupplementaireController;
use App\Http\Controllers\NumeroConfigurationController;

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::post('login_sousUtilisateur', 'login_sousUtilisateur');
    Route::post('logout_sousUtilisateur', 'logout_sousUtilisateur')->withoutMiddleware('auth:api');
    Route::post('refresh_sousUtilisateur', 'refresh_sousUtilisateur')->withoutMiddleware('auth:api'); //->withoutMiddleware('auth:api') permet de ne pas s'ecouter sur la table user;
    Route::get('listerUser', 'listerUser');
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
    Route::post('Des_ArchiverSousUtilisateur/{id}', 'Des_ArchiverSousUtilisateur');
});


Route::controller(PromoController::class)->group(function () {
    Route::post('ajouterPromo', 'ajouterPromo');
    Route::get('listerPromo', 'listerPromo');
    Route::post('modifierPromo/{id}', 'modifierPromo');
    Route::post('supprimerPromo/{id}', 'supprimerPromo');
});

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

});

Route::post('creerFactureAccomp', [FactureAccomptController::class, 'creerFactureAccomp']);
Route::get('listerfactureAccomptsParFacture/{numFacture}', [FactureAccomptController::class, 'listerfactureAccomptsParFacture']);

Route::post('creerFacture', [FactureController::class, 'creerFacture']);
Route::post('listeArticlesFacture/{id_facture}', [FactureController::class, 'listeArticlesFacture']);
Route::get('listerFactures', [FactureController::class, 'listerToutesFactures']);
Route::post('lireFacture/{id}', [FactureController::class, 'lireFacture']);
Route::post('validerFacture/{id}', [FactureController::class, 'validerFacture']);
Route::delete('supprimeArchiveFacture/{id}', [FactureController::class, 'supprimeArchiveFacture']);
Route::get('DetailsFacture/{id}', [FactureController::class, 'DetailsFacture']);

Route::post('listerFacturesEcheance', [FactureController::class, 'listerFacturesEcheance']);
Route::post('listerFacturesAccompt', [FactureController::class, 'listerFacturesAccompt']);
Route::post('listerFacturesPayer', [FactureController::class, 'listerFacturesPayer']);


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


Route::controller(PaiementRecuController::class)->group(function (){
    Route::post('ajouterPaiementRecu','ajouterPaiementRecu');
    Route::post('listPaiementsRecusParFacture/{factureId}', 'listPaiementsRecusParFacture');
    Route::delete('supprimerPaiementRecu/{paiementRecuId}', 'supprimerPaiementRecu');
    Route::post('transformerPaiementRecuEnEcheance/{paiementRecuId}', 'transformerPaiementRecuEnEcheance');
 
});

Route::controller(DeviController::class)->group(function(){
    Route::post('creerDevi','creerDevi');
    Route::post('TransformeDeviEnFacture/{deviId}','TransformeDeviEnFacture');
    Route::post('TransformeDeviEnBonCommande/{deviId}','TransformeDeviEnBonCommande');
    Route::get('listerToutesDevi','listerToutesDevi');
    Route::post('supprimerDevi/{id}','supprimerDevi');
    Route::post('annulerDevi/{deviId}','annulerDevi');    
});

Route::controller(BonCommandeController::class)->group(function(){
    Route::post('creerBonCommande','creerBonCommande');
    Route::post('TransformeBonCommandeEnFacture/{id}','TransformeBonCommandeEnFacture');
    Route::get('listerTousBonCommande','listerTousBonCommande');
    Route::post('supprimerBonCommande/{id}','supprimerBonCommande');
    Route::post('annulerBonCommande/{id}','annulerBonCommande');    
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
});

Route::post('configurerNumeros',[NumeroConfigurationController::class, 'configurerNumeros']);
Route::post('InfoConfigurationFacture',[NumeroConfigurationController::class, 'InfoConfigurationFacture']);