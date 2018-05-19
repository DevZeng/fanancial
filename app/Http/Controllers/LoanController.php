<?php

namespace App\Http\Controllers;

use App\Models\Assess;
use App\Models\BrokerageLog;
use App\Models\Business;
use App\Models\Loan;
use App\Models\ProxyRatio;
use App\Models\Rate;
use App\Models\SysConfig;
use App\Models\WeChatUser;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Mockery\Exception;

class LoanController extends Controller
{
    //
    public function createLoan(Request $post)
    {
        $uid = getUserToken($post->token);
        $business = Business::find($post->business_id);
        $count = Loan::whereDate('created_at', date('Y-m-d', time()))->count();
        $date = date('Y-m-d H:i:s');
        $id = $post->get('id');
        if ($id){
            $loan = Loan::find($id);
        }else{
            $loan = new Loan();
            $loan->user_id = $uid;
        }
        $loan->number = 'DK' . date('Ymd', time()) . sprintf("%03d", $count + 1);
        $loan->name = $post->name? $post->name:$loan->name;
        $loan->phone = $post->phone?$post->phone:$loan->phone;
        $loan->price = $post->price?$post->price:$loan->price;
        $loan->business_id = $post->business_id?$post->business_id:$loan->business_id;
        $loan->brokerage = $business->brokerage?$business->brokerage:$loan->brokerage;
        $loan->state = $post->state?$post->state:$loan->state;
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
    public function listLoans()
    {
        $search = Input::get('search');
        $start = Input::get('start');
        $end = Input::get('end');
        $state = Input::get('state');
        $pay = Input::get('pay');
        $db = DB::table('loans');
        if ($search){
            $db->where('name','=',$search)->orWhere('phone','=',$search);
        }
        if ($start){
            $db->whereBetween('created_at',[$start,$end]);
        }
        if ($state){
            $db->where('state','=',$state);
        }
        if ($pay){
            $db->where('pay','=',$pay);
        }
        $data = $db->orderBy('id','DESC')->get();
        return response()->json([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getLoan($id)
    {
        $loan = Loan::findOrFail($id);
        return response()->json([
            'msg'=>'ok',
            'data'=>$loan
        ]);
    }
    public function changeLoanState($id)
    {
        $loan = Loan::find($id);
        if ($loan->state==1){
            $loan->state = 2;
        }elseif ($loan->state==2){
            $loan->state = 3;
        }else{

        }
        $loan->save();
        return response()->json([
            'msg'=>'ok'
        ]);
    }
    public function payLoan($id)
    {
        DB::beginTransaction();
        try{
            $config = SysConfig::first();
            $loan = Loan::findOrFail($id);
            if ($loan->state!=3){
                return response()->json([
                    'msg'=>'该状态下不能发放贷款！'
                ]);
            }
            $loan->pay = 1;
            $loan->save();
            if ($loan->proxy_id !=0){
                $user = WeChatUser::findOrFail($loan->proxy_id);
                $list = $this->getUsers($user);
                foreach ($list as $item){
                    if ($item->level=='C'){
                        $ratio = ProxyRatio::where('user_id','=',$user->id)->pluck('ratio')->first();
                        $ratio = ($ratio*$config->rate)/100;
                        $price = $loan->brokerage * $ratio;
                    }elseif ($item->level =='B'){
                        $ratio = ProxyRatio::where('user_id','=',$user->id)->pluck('ratio')->first();
                        $ratio = ($ratio*$config->rate)/100;
                        $price = $loan->brokerage * (1-$ratio);
                    }else{
                        $count = WeChatUser::where('proxy_id','=',$item->id)->count();
                        if ($count>3){
                            $ratio = 0.1;
                        }elseif (2<$count && $count<=3){
                            $ratio = 0.05;
                        }else{
                            $ratio = 0.03;
                        }
                        $price = $loan->brokerage * $ratio;
                    }
                    $brokerage = new BrokerageLog();
                    if ($item->id==$loan->proxy_id){
                        $brokerage->type = 1;
                    }else{
                        $brokerage->type = 2;
                    }

                    $brokerage->user_id = $item->id;
                    $brokerage->brokerage = $price;
                    $brokerage->loan_id = $loan->id;
                    $brokerage->save();
                }
//                $list =
//                dd($list);
            }
            DB::commit();
            return response()->json([
                'msg'=>'ok'
            ]);
//            $user = WeChatUser::findOrFail()
        }catch (Exception $exception){
            DB::rollback();
            return response()->json([
                'msg'=>$exception->getMessage()
            ],400);
        }

    }
    public function brokerage($user)
    {
        $user->level = 'C';
        if ($user->proxy_id!=0){
            $swap = WeChatUser::find($user->proxy_id);
            $this->brokerage($swap);
        }
    }
    public function modifyLoanBrokerage(Request $post)
    {
//        $id = $post->id;
        $loan = Loan::find($post->id);
        $loan->brokerage = $post->brokerage?$post->brokerage:$loan->brokerage;
        if ($loan->save()){
            return response()->json([
                'msg'=>'ok'
            ]);
        }
    }
    public function getUsers($user,&$data=[])
    {
        if (!empty($user)){
            if ($user->proxy_id!=0){
                array_push($data,$user);
                $swap = WeChatUser::find($user->proxy_id);
                $this->getUsers($swap,$data);
            }else{
                array_push($data,$user);
            }
        }
        return $data;
    }

    public function createAssess(Request $post)
    {
        $loan = Loan::find($post->loan_id);
        if ($loan->state!=3){
            return response()->json([
                'msg'=>"当前状态不能评价！"
            ]);
        }
        $assess = new Assess();
        $assess->loan_id = $loan->id;
        $assess->before = $post->before;
        $assess->ing = $post->ing;
        $assess->sys = $post->sys;
        $assess->detail = $post->detail;
        if ($assess->save()){
            return response()->json([
                'msg'=>'ok'
            ]);
        }
    }
    public function listBrokerage()
    {
        $limit = Input::get('limit',10);
        $page = Input::get('page',1);
        $name = Input::get('name');
        $date = Input::get('date');
        $db = DB::table('brokerage_logs');
        if ($name){
            $idArr = WeChatUser::where('nickname','=',$name)->pluck('id')->toArray();
            $db->whereIn('proxy_id',$idArr);
        }
        if ($date){
//            $db->whereMonth()
        }
        $list = $db->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        foreach ($list as $item){
            $item->loan = Loan::find($item->loan_id);
            $item->proxy = Loan::find($item->proxy_id);
            $item->user = Loan::find($item->user_id);
        }
        return response()->json([
            'msg'=>'ok',
            'data'=>$list
        ]);
    }
    public function myBrokerage()
    {
        $uid = getUserToken(Input::get('token'));
        $date = Input::get('date');
        $db = BrokerageLog::where('proxy_id','=',$uid)->where('state','=',0)->whereYear('created_at',date('Y',strtotime($date)))->whereMonth('created_at', date('m',strtotime($date)));
        return response()->json([
            'msg'=>'ok',
            'data'=>[
                'amount'=>$db->sum('brokerage'),
                'direct'=>$db->where('type','=',1)->sum('brokerage'),
                'proxy'=>$db->where('type','=',2)->sum('brokerage')
            ]
        ]);
    }

}
