<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\RedirectorController;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\TrackerController;
use App\Http\Controllers\ConsoleController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;

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

    Route::post('/get-mission', [MissionController::class, 'takeMission'])->withoutMiddleware('auth:sanctum');

    Route::post('/confirm-mission', [MissionController::class, 'getConfirmMission'])->withoutMiddleware('auth:sanctum');

    Route::get('/{id}', [RedirectorController::class, 'show']);

    Route::get('/', [RedirectorController::class, 'index']);

    Route::post('/', [RedirectorController::class, 'store']);

    Route::put('/{id}', [RedirectorController::class, 'update']);

    Route::post('/destroy', [RedirectorController::class, 'destroy']);
});

Route::group(['prefix' => 'keyword', 'middleware' => 'auth:sanctum'], function() {

    Route::get('/{id}', [KeywordController::class, 'show']);

    Route::get('/', [KeywordController::class, 'index']);

    Route::post('/', [KeywordController::class, 'store']);

    Route::put('/{id}', [KeywordController::class, 'update']);

    Route::post('/destroy', [KeywordController::class, 'destroy']);
});

Route::group(['prefix' => 'mission', 'middleware' => 'auth:sanctum'], function() {

    Route::get('/take', [MissionController::class, 'takeMission']);

    Route::get('/get', [MissionController::class, 'getMission']);

    Route::get('/code', [MissionController::class, 'getMissionCode'])->withoutMiddleware('auth:sanctum');

    Route::get('/confirm', [MissionController::class, 'getConfirmMission']);

    Route::get('/complete', [MissionController::class, 'getMissionComplete']);

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

Route::group(['prefix' => 'console', 'middleware' => 'auth:sanctum'], function() {

    Route::get('/', [ConsoleController::class, 'index']);
    Route::get('/chart', [ConsoleController::class, 'chart']);
    Route::get('/top-traffic', [ConsoleController::class, 'topTraffic']);
});

Route::group(['prefix' => 'transaction', 'middleware' => 'auth:sanctum'], function() {
    Route::get('/stat', [TransactionController::class, 'stat']);
    Route::post('/convert', [TransactionController::class, 'convert']);
    Route::get('/', [TransactionController::class, 'index']);
    Route::post('/', [TransactionController::class, 'store']);
    Route::put('/{id}', [TransactionController::class, 'store']);
});

Route::group(['prefix' => 'bank', 'middleware' => 'auth:sanctum'], function() {
    Route::get('/', [BankController::class, 'index']);
    Route::post('/', [BankController::class, 'store']);
    Route::put('/{id}', [BankController::class, 'store']);
    Route::post('/destroy', [BankController::class, 'destroy']);
});

Route::group(['prefix' => 'user', 'middleware' => 'auth:sanctum'], function() {
    Route::get('/search', [UserController::class, 'search']);
});
