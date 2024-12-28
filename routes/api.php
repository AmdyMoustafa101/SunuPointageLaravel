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


Route::apiResource('cohortes', CohorteController::class);


Route::apiResource('employes', EmployeController::class);


Route::apiResource('apprenants', ApprenantController::class);

Route::post('login', [EmployeController::class, 'login']);

Route::post('/logout', [EmployeController::class, 'logout'])->middleware('auth:api');

Route::middleware('auth:api')->post('/enregistrer-pointage', [EmployeController::class, 'enregistrerPointage']);
Route::get('/horaires', [PresenceController::class, 'horaires']);

Route::get('/employesC', [UserCardController::class, 'listEmployes']);
Route::get('/apprenantsC', [UserCardController::class, 'listApprenants']);
Route::post('/assign-card', [UserCardController::class, 'assignCard']);
Route::post('/unassign-card', [UserCardController::class, 'unassignCard']);

Route::get('/get-user-by-card/{uid}', [UserCardController::class, 'getUserByCard']);







