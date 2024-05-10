
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
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
    Route::post('modifierRole', 'modifierRole');
    Route::post('supprimerRole', 'supprimerRole');
});


Route::controller(SousUtilisateurController::class)->group(function () {
    Route::post('ajouterUtilisateur', 'ajouterSousUtilisateur');
    Route::get('listeUtilisateurNonArchive', 'listeUtilisateurNonArchive');
    Route::get('listeUtilisateurArchive', 'listeUtilisateurArchive');
    Route::post('modifierSousUtilisateur/{id}', 'modifierSousUtilisateur');
    Route::post('ArchiverSousUtilisateur/{id}', 'ArchiverSousUtilisateur');
});

