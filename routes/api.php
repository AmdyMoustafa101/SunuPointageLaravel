<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CohorteController;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\UserCardController;
use App\Http\Controllers\ApprenantController;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\LeaveRequestController;





Route::apiResource('departements', DepartementController::class);


Route::apiResource('cohortes', CohorteController::class);


Route::apiResource('employes', EmployeController::class);




Route::apiResource('apprenants', ApprenantController::class);

Route::apiResource('apprenants', ApprenantController::class);


Route::post('login', [EmployeController::class, 'login']);

Route::get('/statistics', [EmployeController::class, 'getStatistics']); // Pour Recupérer la liste 

Route::get('App-cohorte', [ApprenantController::class, 'getCounts']);

Route::put('change-password/{email}', [EmployeController::class, 'changePassword']);

Route::get('Emp-dept', [EmployeController::class, 'getCounts']);

Route::post('/logout', [EmployeController::class, 'logout'])->middleware('auth:api');

Route::middleware('auth:api')->post('/enregistrer-pointage', [EmployeController::class, 'enregistrerPointage']);
Route::get('/horaires', [PresenceController::class, 'horaires']);

Route::get('/employesC', [UserCardController::class, 'listEmployes']);

Route::get('/apprenantsC', [UserCardController::class, 'listApprenants']);

Route::delete('/apprenants/{id}', [ApprenantController::class, 'destroy']);
Route::post('/apprenants/delete-multiple', [ApprenantController::class, 'destroyMultiple']);
Route::delete('/apprenants', [ApprenantController::class, 'destroyMultiple']);

Route::get('apprenants/counts', [ApprenantController::class, 'getCounts']);


Route::post('/apprenants/{id}/archive', [ApprenantController::class, 'archive']);

Route::post('/apprenants/archive', [ApprenantController::class, 'archiveMultiple']);

Route::post('/apprenants/{id}/desarchive', [ApprenantController::class, 'desarchiver']);

Route::post('/apprenants/desarchive', [ApprenantController::class, 'desarchiverMultiple']);

// Route::get('/cohorte/{id}/apprenants', [ApprenantController::class, 'getApprenantsByCohorte']);
Route::get('/cohortes/{id}/apprenant', [ApprenantController::class, 'getApprenantsByCohorte']);

Route::put('/apprenants/{id}', [ApprenantController::class, 'update']);

Route::post('/assign-card', [UserCardController::class, 'assignCard']);

Route::post('/unassign-card', [UserCardController::class, 'unassignCard']);

Route::get('/get-user-by-card/{uid}', [UserCardController::class, 'getUserByCard']);

// Routes pour les apprenants actifs et archivés par cohorte
Route::get('/cohortes/{id}/apprenants-actifs', [ApprenantController::class, 'apprenantsActifsByCohorte']);
Route::get('/cohortes/{id}/apprenants-archives', [ApprenantController::class, 'apprenantsArchivesByCohorte']);

Route::post('/cohortes/archive', [CohorteController::class, 'archiveMultiple']);

// Route pour archiver une cohorte
Route::post('/cohortes/{id}/archive', [CohorteController::class, 'archive']);

Route::post('/cohortes/{id}/unarchive', [CohorteController::class, 'unarchive']);


// Route pour archiver plusieurs cohortes
Route::post('/cohortes/archive-multiple', [CohorteController::class, 'archiveMultiple']);

Route::post('cohortes/unarchive-multiple', [CohorteController::class, 'unarchiveMultiple']);

// Route pour obtenir la liste des cohortes
Route::get('/cohortes', [CohorteController::class, 'index']);

Route::get('/departement/{id}', [DepartementController::class, 'getDepartementById']);

Route::get('/departements', [DepartementController::class, 'index']);
Route::patch('departements/{id}/archive', [DepartementController::class, 'archive']);

// Route pour désarchiver un département
Route::post('departements/{id}/desarchiver', [DepartementController::class, 'desarchiver']);

// Route pour récupérer les départements archivés
Route::get('departements/archived', [DepartementController::class, 'getArchivedDepartements']);

// Route pour récupérer le nombre total de départements
Route::get('departements/count', [DepartementController::class, 'getDepartementsCount']);

// Route pour récupérer les départements par horaire
Route::get('departements/by-horaire', [DepartementController::class, 'getDepartementsByHoraire']);

// Route pour archiver plusieurs départements
Route::patch('/departements/archive', [DepartementController::class, 'archiveMultiple']);

// Route pour désarchiver plusieurs départements
Route::patch('/departements/unarchive', [DepartementController::class, 'unarchiveMultiple']);




Route::get('/employes/departement/{departementId}', [EmployeController::class, 'getEmployeesByDepartement']);
Route::get('employes/{id}', [EmployeController::class, 'getEmployeById']);
Route::post('/employes', [EmployeController::class, 'store']);
Route::post('employes/import-csv', [EmployeController::class, 'importCsv']);
Route::prefix('employes')->group(function () {
    Route::post('archive/{id}', [EmployeController::class, 'archive']);
    Route::post('unarchive/{id}', [EmployeController::class, 'unarchive']);
    Route::post('archive-multiple', [EmployeController::class, 'archiveMultiple']);
    Route::post('unarchive-multiple', [EmployeController::class, 'unarchiveMultiple']);
    Route::post('block/{id}', [EmployeController::class, 'block']);
});



Route::apiResource('leave-requests', LeaveRequestController::class);
Route::get('leave-requests/pending', [LeaveRequestController::class, 'getPendingLeave']);



Route::post('cohortes/{id}/unarchive', [CohorteController::class, 'unarchive']);


Route::get('/apprenants/actifs', [ApprenantController::class, 'apprenantsActifs']);

Route::get('/apprenants/archives', [ApprenantController::class, 'apprenantsArchives']);












