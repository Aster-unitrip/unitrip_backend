<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyTypeController;
use App\Http\Controllers\ComponentAttractionController;
use App\Http\Controllers\ComponentCategoryController;
use App\Http\Controllers\MiscController;
use App\Http\Controllers\ImgController;
use App\Http\Controllers\ComponentActivityController;
use App\Http\Controllers\ComponentRestaurantController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\ComponentAccomendationController;
use App\Http\Controllers\ComponentTransportationController; // 現在沒再用
use App\Http\Controllers\ComponentGuideController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ItineraryGroupController;
use App\Http\Controllers\ComponentCarTypeController;
use App\Http\Controllers\PassengerController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\DMController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\EmailController;



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

// Verify email
// Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verifyEmail'])
// ->middleware(['auth', 'signed'])
// ->name('verification.verify');

// // Resend link to verify email
// Route::post('/email/verify/resend', function (Request $request) {
//     $request->user()->sendEmailVerificationNotification();
//     return back()->with('message', 'Verification link sent!');
// })->middleware(['auth', 'signed'])->name('verification.send');

Route::group(['middleware' => 'api', 'prefix' => 'mail'], function ($router) {
    Route::get('/reset/{mail_encryption}/{token}', [EmailController::class, 'get_token']);
    Route::post('/url-link', [EmailController::class, 'mail']);
    Route::post('/reset-password', [EmailController::class, 'reset_password']);

});

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::get('/register-data/{com_no}', [AuthController::class, 'register_data']);
});

Route::group(['middleware'=>'api', 'prefix'=>'companies'], function($router){
    Route::get('/types', [CompanyTypeController::class, 'index']);
});

Route::group(['middleware'=>'api', 'prefix'=>'components'], function($router){
    Route::get('/parent_types', [ComponentCategoryController::class, 'parentCategories']);
    Route::get('/types', [ComponentCategoryController::class, 'childCategories']);
    Route::post('/private2public', [ComponentCategoryController::class, 'copy_from_private_to_public']);
    Route::post('/public2private', [ComponentCategoryController::class, 'copy_from_public_to_private']);
});

Route::group(['middleware'=>'api', 'prefix'=>'img'], function($router){
    Route::post('/logo_upload', [ImgController::class, 'logo_upload']);
    // Route::post('/logo_remove', [ImgController::class, 'logo_remove']);

    Route::group(['middleware'=>'auth'], function(){
        Route::post('/upload', [ImgController::class, 'index']);
        Route::post('/remove', [ImgController::class, 'remove']);
        Route::post('/dm_upload', [ImgController::class, 'dmUpload']);
        Route::post('/dm_remove', [ImgController::class, 'dm_remove']);
    });
});

Route::group(['middleware'=>'api', 'prefix'=>'attractions'], function($router) {
    Route::post('/', [ComponentAttractionController::class, 'add']);
    Route::post('/list', [ComponentAttractionController::class, 'list']);
    Route::get('/{id}', [ComponentAttractionController::class, 'get_by_id']);
    Route::post('/update', [ComponentAttractionController::class, 'edit']);
});

Route::group(['middleware'=>'api', 'prefix'=>'activities'], function($router){
    Route::post('/', [ComponentActivityController::class, 'add']);
    Route::post('/list', [ComponentActivityController::class, 'list']);
    Route::get('/{id}', [ComponentActivityController::class, 'get_by_id']);
    Route::post('/update', [ComponentActivityController::class, 'edit']);
});

Route::group(['middleware'=>'api', 'prefix'=>'misc'], function($router){
    Route::get('/city_town', [MiscController::class, 'cityTown']);
    Route::get('/bank_code', [MiscController::class, 'bankCode']);
    Route::get('/historic_level', [MiscController::class, 'historicLevel']);
    Route::get('/travel_agency_type', [MiscController::class, 'travelAgencyType']);
    Route::get('/organizations', [MiscController::class, 'organization']);
    Route::get('/nationality', [MiscController::class, 'nationality']); //國籍
    Route::get('/order_source', [MiscController::class, 'order_source']); //訂單來源
    Route::get('/company_employee', [MiscController::class, 'company_employee']); //查詢與使用帳號同公司所有員工
    Route::post('/check-duplicate', [MiscController::class, 'check_duplicate']); //檢查是否重複

});

Route::group(['middleware'=>'api', 'prefix'=>'restaurants'], function($router){
    Route::post('/', [ComponentRestaurantController::class, 'add']);
    Route::post('/list', [ComponentRestaurantController::class, 'list']);
    Route::get('/{id}', [ComponentRestaurantController::class, 'get_by_id']);
    Route::post('/update', [ComponentRestaurantController::class, 'edit']);
});

Route::group(['middleware'=>'api', 'prefix'=>'accomendations'], function($router){
    Route::post('/list', [ComponentAccomendationController::class, 'list']);
    Route::post('/', [ComponentAccomendationController::class, 'add']);
    Route::get('/{id}', [ComponentAccomendationController::class, 'get_by_id']);
    Route::post('/update', [ComponentAccomendationController::class, 'edit']);
});

Route::group(['middleware'=>'api', 'prefix'=>'transportations'], function($router){
    Route::post('/list', [ComponentCarTypeController::class, 'list']);
});

Route::group(['middleware'=>'api', 'prefix'=>'guides'], function($router){
    Route::post('/list', [ComponentGuideController::class, 'list']);
});

//訂單
Route::group(['middleware'=>'api', 'prefix'=>'order'], function($router){
    Route::post('/', [OrderController::class, 'add']);
    Route::post('/list', [OrderController::class, 'list']);
    Route::get('/{id}', [OrderController::class, 'get_by_id']);
    Route::post('/update', [OrderController::class, 'edit']);
    Route::post('/operator', [OrderController::class, 'operator']);// 修改旅行社旅客控團預警
});

//團行程
Route::group(['middleware'=>'api', 'prefix'=>'group-itinerary'], function($router){
    Route::get('/component/{id}', [ItineraryGroupController::class, 'get_component_type']); // 可以看到供應商分類表
    Route::get('/{id}', [ItineraryGroupController::class, 'get_by_id']);
    Route::post('/', [ItineraryGroupController::class, 'edit']);
    Route::post('/list', [ItineraryGroupController::class, 'list']);
    Route::post('/save-itinerary', [ItineraryGroupController::class, 'save_to_itinerary']); // 將團行程存回行程範本
    Route::get('/delete-component/{id}', [ItineraryGroupController::class, 'get_delete_items']); // 可以看到供應商刪除類表(all) {id} 團行程ID
    Route::post('/delete-component', [ItineraryGroupController::class, 'edit_delete_items']); // 修改旅行社供應商 待退款改成已退款
    Route::post('/operator', [ItineraryGroupController::class, 'operator']); // 修改旅行社供應商控團預警
});

//dm
Route::group(['middleware'=>'api', 'prefix'=>'dm'], function($router){ //DM系統
    Route::get('/setting/{id}', [DMController::class, 'get_dm_setting']); //取得dm設定資訊
    Route::get('/group-itinerary/{id}', [DMController::class, 'get_dm_group_itinerary']); //取得團行程放入dm
    Route::middleware('auth')->post('/setting', [DMController::class, 'edit_dm_setting']); // 更改DM設定 [是否上架]
});

// 訂單 旅客
Route::group(['middleware'=>'api', 'prefix'=>'passengers'], function($router){
    Route::get('/{id}', [PassengerController::class, 'get_by_order_passenger']); // 取得訂單ID的旅客資料
    Route::post('/', [PassengerController::class, 'edit_order_passenger']); // 修改訂單 passenger 資料
});

// CRM 旅客
Route::group(['middleware'=>'api', 'prefix'=>'crm'], function($router){
    Route::get('/passenger/{id}', [CrmController::class, 'get_by_id']); // 取得 passenger_profile 的單一旅客資料
    Route::post('/passenger', [CrmController::class, 'edit_profile']); // 修改 passenger_profile 單一旅客資料
    Route::post('/profile/list', [CrmController::class, 'profile_list']); // 搜尋旅客管理
    Route::post('/past-orders/list', [CrmController::class, 'past_orders_list']); // 該旅客過往訂單紀錄
});

// 出團預訂單
Route::group(['middleware'=>'api', 'prefix'=>'reservation'], function($router){
    Route::get('/{id}', [ReservationController::class, 'get_by_id']);
    Route::post('/passengers_sheet', [ReservationController::class, 'passengers_sheet']);
    Route::post('/', [ReservationController::class, 'component_pass_to_python']);
});

// 行程
Route::group(['middleware'=>'api', 'prefix'=>'itinerary'], function($router){
    Route::post('/', [ItineraryController::class, 'add']);
    Route::post('/list', [ItineraryController::class, 'list']);
    Route::get('/{id}', [ItineraryController::class, 'get_by_id']);
    Route::post('/update', [ItineraryController::class, 'edit']);
});

// email
Route::group(['middleware'=>'api', 'prefix'=>'email'], function($router){
    Route::get('/', [EmailController::class, 'send']);
});
