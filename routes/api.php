<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApprenantController;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\CohorteController;
use App\Http\Controllers\DepartementController;





Route::apiResource('departements', DepartementController::class);


Route::apiResource('cohortes', CohorteController::class);


Route::apiResource('employes', EmployeController::class);


Route::apiResource('apprenants', ApprenantController::class);

Route::post('login', [EmployeController::class, 'login']);

Route::post('/logout', [EmployeController::class, 'logout'])->middleware('auth:api');

Route::middleware('auth:api')->post('/enregistrer-pointage', [EmployeController::class, 'enregistrerPointage']);






