<?php

use App\Http\Controllers\ResearcherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/researchers', [ResearcherController::class, 'index']);
Route::get('/getresearcherbyorcid/{orcid}', [ResearcherController::class, 'getresearcherbyorcid']);
Route::get('/publicationsbyorcid/{orcid}', [ResearcherController::class, 'publicationsbyorcid']);
Route::get('/getresearcher/{authority}', [ResearcherController::class, 'getresearcher']);
Route::post('/reporterrorinitem', [ResearcherController::class, 'ReportErrorInItem']);
