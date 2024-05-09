
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


//Route::post('/ajouter-utilisateur', [SousUtilisateurController::class, 'ajouterSousUtilisateur']);