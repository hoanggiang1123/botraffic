<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RedirectorController;
use App\Http\Controllers\KeywordController;

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

// Route::group(['prefix' => 'mission'], function() {
//     Route::post('/', [RedirectorController::class, 'getMission']);
// });
Route::group(['prefix' => 'redirector'], function() {
    Route::post('/', [RedirectorController::class, 'store']);
    Route::post('/get-mission', [RedirectorController::class, 'getMission']);
    Route::post('/get-mission-code', [RedirectorController::class, 'getMissionCode']);
    Route::post('/confirm-mission', [RedirectorController::class, 'confirmMission']);
});

Route::group(['prefix' => 'keyword', 'middleware' => 'auth:sanctum'], function() {
    Route::get('/', [KeywordController::class, 'index']);
    Route::post('/', [KeywordController::class, 'store']);
    Route::put('/{id}', [KeywordController::class, 'update']);
    Route::post('/destroy', [KeywordController::class, 'destroy']);
});

