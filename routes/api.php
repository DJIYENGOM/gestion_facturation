
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategorieClientController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SousUtilisateurController;

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::post('login_sousUtilisateur', 'login_sousUtilisateur');
    Route::post('logout_sousUtilisateur', 'logout_sousUtilisateur')->withoutMiddleware('auth:api');
    Route::post('refresh_sousUtilisateur', 'refresh_sousUtilisateur')->withoutMiddleware('auth:api'); //->withoutMiddleware('auth:api') permet de ne pas s'ecouter sur la table user;
});


Route::controller(RoleController::class)->group(function () {
    Route::post('ajouterRole', 'ajouterRole');
    Route::get('listerRole', 'listerRole');
});


Route::controller(SousUtilisateurController::class)->group(function () {
    Route::post('ajouterUtilisateur', 'ajouterSousUtilisateur');
    Route::get('listeUtilisateurNonArchive', 'listeUtilisateurNonArchive');
    Route::get('listeUtilisateurArchive', 'listeUtilisateurArchive');
    Route::post('modifierSousUtilisateur/{id}', 'modifierSousUtilisateur');
    Route::post('ArchiverSousUtilisateur/{id}', 'ArchiverSousUtilisateur');
});


Route::controller(PromoController::class)->group(function () {
    Route::post('ajouterPromo', 'ajouterPromo');
    Route::post('supprimerPromo/{id}', 'supprimerPromo');
});

Route::post('ajouterArticle', [ArticleController::class, 'ajouterArticle']);
Route::get('listerArticles', [ArticleController::class, 'listerArticles']);
Route::post('modifierArticle/{id}', [ArticleController::class, 'modifierArticle']);
Route::delete('supprimerArticle/{id}', [ArticleController::class, 'supprimerArticle']);
Route::post('affecterPromoArticle/{id}', [ArticleController::class, 'affecterPromoArticle']);


Route::controller(CategorieClientController::class)->group(function () {
    Route::post('ajouterCategorie', 'ajouterCategorie');
    Route::get('listerCategorieClient', 'listerCategorieClient');
    Route::post('modifierCategorie/{id}', 'modifierCategorie');
    Route::delete('supprimerCategorie/{id}', 'supprimerCategorie');

});