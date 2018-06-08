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
//Route::get('test',function (Request $post){
//    dd();
//});
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('banners','BannerController@getBanners');
Route::post('login','UserController@WXLogin');
Route::get('businesses','BusinessController@Businesses');
Route::get('business/{id}','BusinessController@getBusiness');
Route::get('qrcode','UserController@get_qrcode');
Route::get('config','SystemController@getConfig');
Route::get('check/signature','SystemController@checkWeChat');
Route::post('wx/test','WeChatController@getToken');
Route::get('touch','WeChatController@touch');
Route::get('check/subscribe','WeChatController@check');
Route::get('upgrade','UserController@upgrade');
Route::post('ratio','UserController@editRatio');

Route::post('rate','UserController@editRate');
Route::get('my/scan/record','UserController@scanRecord');
Route::group(['middleware'=>'wx'],function (){
   Route::get('info','UserController@getInfo');
   Route::post('info','UserController@setInfo');
   Route::post('apply','UserController@applyProxy');
   Route::get('apply','UserController@getApply');
   Route::post('loan','LoanController@createLoan');
   Route::get('loans','LoanController@myLoans');
   Route::delete('loan/{id}','LoanController@cancelLoan');
   Route::get('loans/count','LoanController@myLoanCount');
   Route::get('scan','UserController@scan');
   Route::get('my/agents','UserController@myAgents');
   Route::get('my/code','UserController@myCode');
   Route::get('my/message','UserController@myMessage');
   Route::post('withdraw/apply','UserController@createWithdrawApply');
   Route::post('assess','LoanController@createAssess');
   Route::get('my/brokerage','LoanController@myBrokerage');
   Route::get('my/bill','UserController@myBill');
   Route::get('my/data','UserController@myData');
   Route::get('my/withdraw','UserController@myWithoutRecord');
   Route::get('my/apply','UserController@myApply');
   Route::get('my/proxy/apply','UserController@myProxyApply');
   Route::get('check/apply','UserController@checkApply');
   Route::get('user','UserController@getUserByToken');
});