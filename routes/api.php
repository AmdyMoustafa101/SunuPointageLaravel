<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApprenantController;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\CohorteController;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\UserCardController;





Route::apiResource('departements', DepartementController::class);
Route::post('/departements/import', [DepartementController::class, 'importCsv']);


Route::apiResource('cohortes', CohorteController::class);
Route::post('/cohortes/import', [CohorteController::class, 'importCsv']);


Route::apiResource('employes', EmployeController::class);
Route::post('/employes/import', [EmployeController::class, 'importCsv']);
Route::put('/change-password/{email}', [EmployeController::class, 'changePassword']);
Route::post('/employes/{id}/update-photo', [EmployeController::class, 'updatePhoto']);
Route::post('/employes/{id}/change-password1', [EmployeController::class, 'changePassword1']);
Route::get('departements', [EmployeController::class, 'getDepartements']);
Route::get('/departements', [DepartementController::class, 'getDepartements']);
Route::get('employes/export/csv', [EmployeController::class, 'exportCSV']);
Route::get('employes/export/excel', [EmployeController::class, 'exportExcel']);



Route::apiResource('apprenants', ApprenantController::class);
Route::post('/apprenants/import', [ApprenantController::class, 'importCsv']);

Route::post('login', [EmployeController::class, 'login']);
Route::post('login-by-card', [EmployeController::class, 'loginByCard']);

Route::get('Emp-dept', [EmployeController::class, 'getCounts']);
Route::get('App-cohorte', [ApprenantController::class, 'getCounts']);


Route::get('App-cohorte', [ApprenantController::class, 'getCounts']);

Route::put('change-password/{email}', [EmployeController::class, 'changePassword']);


Route::get('Emp-dept', [EmployeController::class, 'getCounts']);

Route::post('/logout', [EmployeController::class, 'logout'])->middleware('auth:api');

Route::middleware('auth:api')->post('/enregistrer-pointage', [EmployeController::class, 'enregistrerPointage']);
Route::get('/horaires', [PresenceController::class, 'horaires']);
Route::get('/employes', [EmployeController::class, 'getEmployes']);
Route::put('/{id}', [EmployeController::class, 'update']);
Route::put('/{id}', [ApprenantController::class, 'update']);


Route::get('/employesC', [UserCardController::class, 'listEmployes']);
Route::get('/apprenantsC', [UserCardController::class, 'listApprenants']);
Route::post('/assign-card', [UserCardController::class, 'assignCard']);
Route::post('/unassign-card', [UserCardController::class, 'unassignCard']);

Route::get('/get-user-by-card/{uid}', [UserCardController::class, 'getUserByCard']);

Route::get('apprenants', [ApprenantController::class, 'indexB']);
Route::get('cohortes', [ApprenantController::class, 'getCohortes']);
Route::get('apprenants/export/csv', [ApprenantController::class, 'exportCSV']);
Route::get('apprenants/export/excel', [ApprenantController::class, 'exportExcel']);






