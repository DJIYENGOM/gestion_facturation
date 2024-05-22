
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategorieArticleController;
use App\Http\Controllers\CategorieClientController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\Info_SupplementaireController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SousUtilisateurController;
use App\Http\Controllers\NoteJustificativeController;

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
    Route::get('listerCategorieArticle', 'listerCategorie');
    Route::post('modifierCategorieArticle/{id}', 'modifierCategorie');
    Route::delete('supprimerCategorieArticle/{id}', 'supprimerCategorie');
});