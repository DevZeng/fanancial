<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Loan;
use App\Models\WeChatUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class LoanController extends Controller
{
    //
    public function createLoan(Request $post)
    {
        $uid = getUserToken($post->token);
        $loan = new Loan();
        $business = Business::find($post->business_id);
        $count = Loan::whereDate('created_at', date('Y-m-d', time()))->count();
        $date = date('Y-m-d H:i:s');
        $loan->number = 'DK' . date('Ymd', time()) . sprintf("%03d", $count + 1);
        $loan->name = $post->name;
        $loan->phone = $post->phone;
        $loan->price = $post->price;
        $loan->business_id = $post->business_id;
        $loan->brokerage = $business->brokerage;
        $loan->user_id = $uid;
        $loan->save();
        return response()->json([
            'msg'=>'ok',
            'data'=>[
                'number'=>$loan->number,
                'time'=>$date,
                'name'=>$loan->name,
                'phone'=>$loan->phone,
                'state'=>'待处理',
                'type'=>$business->name
            ]
        ]);
    }
    public function myLoans()
    {
        $uid = getUserToken(Input::get('token'));
        $state = Input::get('state',1);
        $limit = Input::get('limit',10);
        $page = Input::get('page',1);
        $loan = Loan::where('user_id','=',$uid)->where('state','=',$state)->limit($limit)->offset(($page-1)*$limit)->get();
        return response()->json([
            'msg'=>'ok',
            'data'=>$loan
        ]);
    }
    public function myLoanCount()
    {
        $uid = getUserToken(Input::get('token'));
        $user = WeChatUser::find($uid);
        $wait = $user->loans()->where('state','=',1)->count();
        $handle = $user->loans()->where('state','=',2)->count();
        $finish = $user->loans()->where('state','=',3)->count();
        $cancel = $user->loans()->where('state','=',0)->count();
        return response()->json([
            'msg'=>'ok',
            'data'=>[
                'wait'=>$wait,
                'handle'=>$handle,
                'finish'=>$finish,
                'cancel'=>$cancel,
            ]
        ]);
    }
}
