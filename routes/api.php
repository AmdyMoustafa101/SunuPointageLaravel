<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApprenantController;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\CohorteController;
use App\Http\Controllers\DepartementController;



// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Exemple de route API pour un contrôleur


Route::apiResource('departements', DepartementController::class);


Route::apiResource('cohortes', CohorteController::class);


Route::apiResource('employes', EmployeController::class);


Route::apiResource('apprenants', ApprenantController::class);

Route::post('login', [EmployeController::class, 'login']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



