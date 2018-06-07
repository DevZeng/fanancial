<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::options('{all}',function (){return 'ok';})->middleware('cross');
//Route::options('{all}/{all}',function (){return 'ok';})->middleware('cross');

Route::get('test',function (){
    $date = '2018-4';
    return date('Y',strtotime($date)).date('m',strtotime($date));
//    return uniqid();
});
Route::post('login','UserController@login');
Route::get('logout','UserController@logout');
Route::post('upload','SystemController@upload');

Route::get('weixin/callback','UserController@WeChatCallback');
Route::get('weixin','UserController@redirectToProvider');

Route::get('users','UserController@listUsers');
Route::group(['middleware'=>['cross','auth']],function (){

    Route::post('permission','UserController@createPermission');
    Route::get('permissions','UserController@getPermissions');
    Route::post('role','UserController@createRole');
    Route::get('roles','UserController@roles');
    Route::get('role/{id}','UserController@getRole');
    Route::delete('role/{id}','UserController@deleteRole');
//    Route::delete('role/{id}','UserController@getRole');

    Route::get('access/token','UserController@get_qrcode');


    Route::get('config','SystemController@getConfig');
    Route::post('config','SystemController@editConfig');

    Route::post('banner','BannerController@createBanner');
    Route::delete('banner/{id}','BannerController@delBanner');
    Route::put('banner/{id}','BannerController@editBanner');
    Route::get('banner/{id}','BannerController@getBanner');
    Route::get('banners','BannerController@getBanners');
    Route::post('business','BusinessController@create');
    Route::get('businesses','BusinessController@getAllBusinesses');
//        Route::put('business/{id}','BusinessController@edit');
    Route::delete('business/{id}','BusinessController@delete');
    Route::get('business/{id}','BusinessController@getBusiness');
    Route::get('types','BusinessController@getTypes');
    Route::post('user','UserController@editUser');
    Route::get('admins','UserController@listAdmin');
    Route::get('admin/{id}','UserController@getAdmin');
    Route::delete('admin/{id}','UserController@deleteAdmin');
    Route::post('admin','UserController@createUser');
    Route::get('agents','UserController@listAgents');
    Route::post('agent','UserController@modifyAgent');
    Route::get('applies','UserController@listApplies');
    Route::get('check/apply','UserController@checkApply');
    Route::get('messages','UserController@searchMessage');
    Route::get('count','UserController@countLoan');
    Route::get('loans','LoanController@listLoans');
    Route::get('loan/{id}','LoanController@getLoan');
    Route::post('loan','LoanController@editLoan');
//    Route::get('agent/{id}','UserController@getProxy');
    Route::get('pay/loan/{id}','LoanController@payLoan');
    Route::get('change/loan/{id}','LoanController@changeLoanState');
    Route::post('loan/brokerage','LoanController@modifyLoanBrokerage');
    Route::get('brokerages','LoanController@listBrokerage');
    Route::get('user/data','UserController@userData');
    Route::get('user/withdraw/list','UserController@listWithoutRecord');
    Route::get('withdraw/{id}','UserController@payApply');
    Route::get('tree/agents','UserController@listAgentTree');
    Route::get('agent/record','UserController@listAgentRecord');
    Route::delete('user/{id}','UserController@disableUser');
//    Route::group(['middleware'=>'auth'],function (){
//        Route::post('banner','BannerController@createBanner');
//        Route::delete('banner/{id}','BannerController@delBanner');
//        Route::put('banner/{id}','BannerController@editBanner');
//        Route::get('banner/{id}','BannerController@getBanner');
//        Route::get('banners','BannerController@getBanners');
//        Route::post('business','BusinessController@create');
//        Route::get('businesses','BusinessController@getBusinesses');
////        Route::put('business/{id}','BusinessController@edit');
//        Route::delete('business/{id}','BusinessController@delete');
//        Route::get('business/{id}','BusinessController@getBusiness');
//        Route::get('types','BusinessController@getTypes');
//        Route::get('users','UserController@listUsers');
//        Route::get('applies','UserController@listApplies');
//    });
});


Route::get('/', function () {
    return view('welcome');
});
