<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RedirectorController;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\TrackerController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'auth'], function() {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');
    Route::post('/social-login', [AuthController::class, 'socialLogin']);
});


Route::group(['prefix' => 'redirector', 'middleware' => 'auth:sanctum'], function() {
    Route::get('/{id}', [RedirectorController::class, 'show']);
    Route::get('/', [RedirectorController::class, 'index']);
    Route::post('/', [RedirectorController::class, 'store']);
    Route::put('/{id}', [RedirectorController::class, 'update']);
    Route::post('/destroy', [RedirectorController::class, 'destroy']);

    // Route::post('/', [RedirectorController::class, 'store']);
    // Route::post('/get-mission', [RedirectorController::class, 'getMission']);
    // Route::post('/get-mission-code', [RedirectorController::class, 'getMissionCode']);
    // Route::post('/confirm-mission', [RedirectorController::class, 'confirmMission']);
});

Route::group(['prefix' => 'keyword', 'middleware' => 'auth:sanctum'], function() {
    Route::get('/{id}', [KeywordController::class, 'show']);
    Route::get('/', [KeywordController::class, 'index']);
    Route::post('/', [KeywordController::class, 'store']);
    Route::put('/{id}', [KeywordController::class, 'update']);
    Route::post('/destroy', [KeywordController::class, 'destroy']);
});

Route::group(['prefix' => 'mission', 'middleware' => 'auth:sanctum'], function() {
    Route::get('/{id}', [MissionController::class, 'show']);
    Route::get('/', [MissionController::class, 'index']);
    Route::post('/', [MissionController::class, 'store']);
    Route::put('/{id}', [MissionController::class, 'update']);
    Route::post('/destroy', [MissionController::class, 'destroy']);
});

Route::group(['prefix' => 'media', 'middleware' => 'auth:sanctum'], function() {
    Route::post('/', [MediaController::class, 'index']);
});

Route::group(['prefix' => 'tracker', 'middleware' => 'auth:sanctum'], function() {
    Route::get('/', [TrackerController::class, 'index']);
});
