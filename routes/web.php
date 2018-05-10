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
    return uniqid();
});
Route::post('permission','UserController@createPermission');
Route::get('permissions','UserController@getPermissions');
Route::post('role','UserController@createRole');
Route::get('roles','UserController@roles');
Route::group(['middleware'=>'cross'],function (){
    Route::post('login','UserController@login');
    Route::get('access/token','UserController@get_qrcode');
    Route::get('logout','UserController@logout');
    Route::post('upload','SystemController@upload');

    Route::post('banner','BannerController@createBanner');
    Route::delete('banner/{id}','BannerController@delBanner');
    Route::put('banner/{id}','BannerController@editBanner');
    Route::get('banner/{id}','BannerController@getBanner');
    Route::get('banners','BannerController@getBanners');
    Route::post('business','BusinessController@create');
    Route::get('businesses','BusinessController@getBusinesses');
//        Route::put('business/{id}','BusinessController@edit');
    Route::delete('business/{id}','BusinessController@delete');
    Route::get('business/{id}','BusinessController@getBusiness');
    Route::get('types','BusinessController@getTypes');
    Route::get('users','UserController@listUsers');
    Route::post('user','UserController@createUser');
    Route::get('agents','UserController@listAgents');
    Route::get('applies','UserController@listApplies');
    Route::get('check/apply','UserController@checkApply');
    Route::get('loans','LoanController@listLoans');
    Route::get('loan/{id}','LoanController@getLoan');
    Route::get('agent/{id}','UserController@getProxy');
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
