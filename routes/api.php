<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('banners','BannerController@getBanners');
Route::post('login','UserController@WXLogin');
Route::get('businesses','BusinessController@Businesses');
Route::get('business/{id}','BusinessController@getBusiness');
Route::group(['middleware'=>'wx'],function (){
   Route::get('info','UserController@getInfo');
   Route::post('info','UserController@setInfo');
   Route::post('apply','UserController@applyProxy');
   Route::get('apply','UserController@getApply');
   Route::post('loan','LoanController@createLoan');
   Route::get('loans','LoanController@myLoans');
   Route::get('loans/count','LoanController@myLoanCount');
   Route::get('qrcode','UserController@get_qrcode');
});