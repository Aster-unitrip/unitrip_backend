<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyTypeController;
use App\Http\Controllers\ComponentAttractionController;
use App\Http\Controllers\ComponentCategoryController;
use App\Http\Controllers\MiscController;
use App\Http\Controllers\ImgController;


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

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
});

Route::group(['middleware'=>'api', 'prefix'=>'companies'], function($router){
    Route::get('/types', [CompanyTypeController::class, 'index']);
});

Route::group(['middleware'=>'api', 'prefix'=>'components'], function($router){
    Route::get('/parent_types', [ComponentCategoryController::class, 'parentCategories']);
    Route::get('/types', [ComponentCategoryController::class, 'childCategories']);
});

Route::group(['middleware'=>'api', 'prefix'=>'img'], function($router){
    Route::post('/upload', [ImgController::class, 'index']);
    Route::post('/remove', [ImgController::class, 'remove']);
});

Route::group(['middleware'=>'api', 'prefix'=>'attractions'], function($router){
    Route::post('/', [ComponentAttractionController::class, 'add2']);
    Route::post('/list', [ComponentAttractionController::class, 'list']);
    Route::get('/{id}', [ComponentAttractionController::class, 'get_by_id']);
    Route::post('/update', [ComponentAttractionController::class, 'edit']);
});

Route::group(['middleware'=>'api', 'prefix'=>'misc'], function($router){
    Route::get('/city_town', [MiscController::class, 'cityTown']);
    Route::get('/bank_code', [MiscController::class, 'bankCode']);
    Route::get('/historic_level', [MiscController::class, 'historicLevel']);
});