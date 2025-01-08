<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CohorteController;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\UserCardController;
use App\Http\Controllers\ApprenantController;
use App\Http\Controllers\DepartementController;





Route::apiResource('departements', DepartementController::class);


Route::apiResource('cohortes', CohorteController::class);


Route::apiResource('employes', EmployeController::class);




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
Route::post('/apprenants/{id}/archive', [ApprenantController::class, 'archive']);

Route::post('/apprenants/archive', [ApprenantController::class, 'archiveMultiple']);

// Route::get('/cohorte/{id}/apprenants', [ApprenantController::class, 'getApprenantsByCohorte']);
Route::get('/cohortes/{id}/apprenant', [ApprenantController::class, 'getApprenantsByCohorte']);

Route::put('/apprenants/{id}', [ApprenantController::class, 'update']);

Route::post('/assign-card', [UserCardController::class, 'assignCard']);

Route::post('/unassign-card', [UserCardController::class, 'unassignCard']);

Route::get('/get-user-by-card/{uid}', [UserCardController::class, 'getUserByCard']);







