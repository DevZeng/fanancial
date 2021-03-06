<?php

namespace App\Http\Controllers;

use App\Libraries\WxNotify;
use App\Libraries\Wxxcx;
use App\Models\Assess;
use App\Models\BrokerageLog;
use App\Models\Business;
use App\Models\Loan;
use App\Models\LoanLog;
use App\Models\ProxyApply;
use App\Models\ProxyRatio;
use App\Models\Rate;
use App\Models\SysConfig;
use App\Models\WeChatUser;
use App\User;
use function GuzzleHttp\Psr7\uri_for;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Mockery\Exception;

class LoanController extends Controller
{
    //
    public function createLoan(Request $post)
    {
        $uid = getUserToken($post->token);
        $user = WeChatUser::find($uid);
        if($user->state==0){
            return response()->json([
                'msg'=>'用户已被禁用！'
            ]);
        }
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
        $loan->business = Business::find($post->business_id)->name;
        $loan->brokerage = $business->brokerage?($business->brokerage/100)*$post->price:$loan->brokerage;
        $loan->state = $post->state?$post->state:1;
        $loan->formId = $post->formId?$post->formId:'';
        $loan->proxy_id = $user->proxy_id;
        $loan->save();

        return response()->json([
            'msg'=>'ok',
            'data'=>[
                'number'=>$loan->number,
                'time'=>$date,
                'name'=>$loan->name,
                'phone'=>$loan->phone,
                'price'=>$loan->price,
                'state'=>'待处理',
                'type'=>$business->name
            ]
        ]);
    }
    public function myLoans()
    {
        $uid = getUserToken(Input::get('token'));
        $state = Input::get('state',0);
        $limit = Input::get('limit',10);
        $page = Input::get('page',1);
        $loan = Loan::where('user_id','=',$uid)->where('state','=',$state)->limit($limit)->offset(($page-1)*$limit)->get();
//        if (!empty($loan)){
//            foreach ($loan as $item){
////                dd($item);
//                $business = Business::find($item->business_id);
////                dd($business);
//                $item->business = $business?$business->name:'无数据';
//            }
//        }
        return response()->json([
            'msg'=>'ok',
            'data'=>$loan
        ]);
    }
    public function cancelLoan($id)
    {
        $uid = getUserToken(Input::get('token'));
        $loan = Loan::find($id);
        if ($loan->user_id != $uid){
            return response()->json([
                'msg'=>'无权操作！'
            ],403);
        }
        $loan->state = 0;
        $loan->save();
        return response()->json([
            'msg'=>'ok'
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
                'apply'=>ProxyApply::where('type','=',1)->where('state','=',0)->where('code','=',$user->code)->count()
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
        $number = Input::get('number');
        $limit = Input::get('limit',10);
        $page = Input::get('page',1);
        $db = DB::table('loans');
        if ($search){
            $db->where('name','like','%'.$search.'%')->orWhere('phone','like','%'.$search.'%');
        }
        if ($number){
            $db->where('number','like','%'.$number.'%');
        }
        if ($start){
            $db->whereBetween('created_at',[$start,$end]);
        }
        if ($state){
            $db->where('state','=',$state-1);
        }
        if ($pay){
            $db->where('pay','=',$pay-1);
        }
        $count = $db->count();
        $data = $db->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();

        if (!empty($data)){
            foreach ($data as $item){
                $user = WeChatUser::find($item->proxy_id);
                $item->proxy_id = $user?$user->name:'';
            }
        }
//        if (!empty($data)){
//            foreach ($data as $datum){
//
//                $datum->business = Business::find($datum->business_id)->name;
//            }
//        }
        return response()->json([
            'msg'=>'ok',
            'count'=>$count,
            'data'=>$data
        ]);
    }
    public function getLoan($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->log = LoanLog::where('loan_id','=',$loan->id)->get();
        return response()->json([
            'msg'=>'ok',
            'data'=>$loan
        ]);
    }
    public function editLoan(Request $post)
    {
        $id = $post->id;
        $loan = Loan::findOrFail($id);
        $loan->name = $post->name?$post->name:$loan->name;
        $loan->phone = $post->phone?$post->phone:$loan->phone;
        $loan->price = $post->price?$post->price:$loan->price;
        $loan->business_id = $post->business_id?$post->business_id:$loan->business_id;
        $loan->remark = $post->note?$post->note:$loan->remark;
        if ($loan->save()){
            return response()->json([
                'msg'=>'ok'
            ]);
        }
    }
    public function changeLoanState($id)
    {
//        dd(Input::all());
        $loan = Loan::find($id);
        if ($loan->state==1){
            $loan->state = 2;
            $log = new LoanLog();
//            dd($loan);
            $log->user_id = Auth::id();
            $log->loan_id = $id;
            $log->detail = '状态由待处理变成处理中';
//            dd(Auth::id());
//            dd(Input::all());
//            $user = Auth::user();
//            dd($user);
            $log->username = Auth::user()->username;
//            $user = Auth::user();
//            dd($user);
            $log->save();
            $user = WeChatUser::find($loan->user_id);
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
//            $code = $post->code;
            $url = sprintf($url,config('wxxcx.appId'),config('wxxcx.appSecret'));
            $wechat = new Wxxcx(config('wxxcx.app_id'),config('wxxcx.app_secret'));
            $data = $wechat->request($url);
            var_dump($url);
            var_dump($data);
            if (isset($data['access_token'])){
                var_dump($data);
                $sendUrl = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$data['access_token'];
                $data = [
                    "touser"=>$user->open_id,
                    "template_id"=>config('wxxcx.templateId'),
//                    "form_id"=> $loan->formId,
                    "url"=>"https://www.gzzrdc.com/Orderlist?oId=2",
                    "data"=>[
                        "first"=>[
                            'value'=>'您好，您有一条订单变更消息提醒'
                        ],
                        "keyword1"=>[
                            "value"=>$loan->number
                        ],
                        "keyword2"=>[
                            "value"=>'已受理'
                        ],
                        "keyword3"=>[
                            "value"=>"您的订单已经收到，客服正在为您处理订单～"
                        ],
                        "remark"=>[
                            "value"=>"业务员尽快与您联系！"
                        ]
                    ]
                ];
                $receive = $wechat->request($sendUrl,json_encode($data));
                var_dump($receive);
//                $wechat->send(json_encode($data));
            }


//            $wx = new WxNotify(config('wxxcx.app_id'),config('wxxcx.app_secret'));

        }elseif ($loan->state==2){
            $loan->state = 3;
            $log = new LoanLog();
            $log->user_id = Auth::id();
            $log->loan_id = $id;
            $log->detail = '状态由处理中变成已完成';
//            $user = Auth::user();
//            dd($user);
            $log->username = Auth::user()->username;
            $log->save();
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
            if ($loan->state!=3||$loan->pay==1){
                return response()->json([
                    'msg'=>'该状态下不能发放贷款！'
                ]);
            }
            $loan->pay = 1;
            $loan->save();
//            dd($config);
            if ($loan->proxy_id !=0){
                $user = WeChatUser::findOrFail($loan->proxy_id);
//                dd($user);
                $list = $this->getUsers($user);
//                dd($list);
                $swap = 0;
                $userName = '';
                for ($i=0;$i<count($list);$i++){
                    $brokerage = new BrokerageLog();
                    if ($i==0){
                        $brokerage->type = 1;
                        $ratio = ProxyRatio::where('user_id','=',$list[$i]->id)->pluck('ratio')->first();
                        $ratio = $ratio?$ratio:60;
                        $ratio = ($ratio/100)*($config->rate/100);
                        $swap = $ratio;
                        $price = $loan->brokerage * $ratio;
                    }elseif($i==1){
                        $brokerage->type = 2;
                        $userName = $list[$i]->name;
                        $ratio = $config->rate/100;
                        $price = $loan->brokerage * ($ratio-$swap);
                    }elseif($i==2){
                        $brokerage->type = 3;
                        if ($userName!=''){
                            $brokerage->remark = '来自'.$userName.'的奖励';
                        }
                        $count = WeChatUser::where('proxy_id','=',$list[$i]->id)->count();
                        if ($count>3){
                            $ratio = 0.1;
                        }elseif (2<$count && $count<=3){
                            $ratio = 0.05;
                        }else{
                            $ratio = 0.03;
                        }
                        $price = $loan->brokerage * $ratio;
                    }
                    $brokerage->user_id = $list[$i]->id;
                    $brokerage->proxy_id = $list[$i]->id;
                    $brokerage->brokerage = $price;
                    $brokerage->loan_id = $loan->id;
//                    dd($brokerage);
                    $brokerage->save();
                }
//                foreach ($list as $item){
//                    $brokerage = new BrokerageLog();
//                    if ($item->level=='C'){
//                        $brokerage->type = 1;
//                        $ratio = ProxyRatio::where('user_id','=',$item->id)->pluck('ratio')->first();
//                        $ratio = ($ratio/100)*($config->rate/100);
//                        $swap = $ratio;
//                        $price = $loan->brokerage * $ratio;
//                        $userName = $item->name;
//                    }elseif ($item->level =='B'){
//                        $userName = $item->name;
//                        if ($item->id==$loan->proxy_id){
//                            $brokerage->type = 1;
//                        }else{
//                            $brokerage->type = 2;
//                        }
////                        $ratio = ProxyRatio::where('user_id','=',$item->id)->pluck('ratio')->first();
////                        if ($ratio){
////                            $ratio = ($ratio/100)*($config->rate/100);
////                        }else{
//                            $ratio = $config->rate/100;
////                        }
//                        $price = $loan->brokerage * ($ratio-$swap);
//                    }else{
//                        if ($item->id==$loan->proxy_id){
//                            $brokerage->type = 1;
//                            $ratio = $config->rate/100;
//                            $price = $loan->brokerage * ($ratio-$swap);
//                        }else{
//                            $brokerage->type = 3;
//                            if ($userName!=''){
//                                $brokerage->remark = '来自'.$userName.'的奖励';
//                            }
//                            $count = WeChatUser::where('proxy_id','=',$item->id)->count();
//                            if ($count>3){
//                                $ratio = 0.1;
//                            }elseif (2<$count && $count<=3){
//                                $ratio = 0.05;
//                            }else{
//                                $ratio = 0.03;
//                            }
//                            $price = $loan->brokerage * $ratio;
//                        }
//
//
//
//                    }
//
//                    $brokerage->user_id = $item->id;
//                    $brokerage->proxy_id = $item->id;
//                    $brokerage->brokerage = $price;
//                    $brokerage->loan_id = $loan->id;
////                    dd($brokerage);
//                    $brokerage->save();
//                }
//                $list =
//                dd($list);
            }

            $log = new LoanLog();
            $log->user_id = 1;
            $log->user_id = Auth::id();
            $log->loan_id = $id;
            $log->detail = '发放贷款';
            $log->username = Auth::user()->username;
//            $log->username = 'devzeng';
            $log->save();
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
        $ratio = $post->ratio;
        if ($ratio){
            $loan->brokerage = $loan->price * ($ratio/100);
        }
        if ($loan->brokerage>$loan->price){
            return response()->json([
                'msg'=>'返佣金额不能大于贷款金额'
            ],400);
        }
        if ($loan->save()){
            return response()->json([
                'msg'=>'ok'
            ]);
        }
    }
    public function getUsers($user,&$data=[])
    {

        for ($i=0;$i<=2;$i++){
//            var_dump($user[$i]);
            if (!empty($user)){
                if ($user->proxy_id!=0){
                    array_push($data,$user);
                    $user = WeChatUser::find($user->proxy_id);
//                var_dump($swap);
//                    $this->getUsers($swap,$data);
                }else{
                    array_push($data,$user);
                    $user = null;
                }
            }
        }
//        dd($data);
//        dd($user);
//        if (!empty($user)){
////            if ($user->level!='A'){
//                if ($user->proxy_id!=0&&$user->level!='A'){
//                    array_push($data,$user);
//                    $swap = WeChatUser::find($user->proxy_id);
////                var_dump($swap);
//                    $this->getUsers($swap,$data);
//                }else{
//                    array_push($data,$user);
//                }
////            }
//        }
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
        $level = Input::get('level');
        $db = DB::table('brokerage_logs');
        if ($level){
            $idArr = WeChatUser::where('level','=',$level)->pluck('id')->toArray();
            $db->whereIn('proxy_id',$idArr);
        }
        if ($name){
            $idArr = WeChatUser::where('nickname','like','%'.$name.'%')->orWhere('name','like','%'.$name.'%')->pluck('id')->toArray();
            $db->whereIn('proxy_id',$idArr);
        }
        if ($date){
            $date = strtotime($date);
            $db->whereYear('created_at',date('Y',$date))->whereMonth('created_at', date('m',$date));
        }
        $count = $db->count();
        $list = $db->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        foreach ($list as $item){
            $item->loan = Loan::find($item->loan_id);
            $item->proxy = WeChatUser::find($item->proxy_id);
//            $item->user = Loan::find($item->user_id);
        }
        return response()->json([
            'msg'=>'ok',
            'count'=>$count,
            'data'=>$list
        ]);
    }
    public function myBrokerage()
    {
        $uid = getUserToken(Input::get('token'));
        $date = Input::get('date');
        $date = explode('/',$date);
        if (count($date)!=2){
            return response()->json([
                'msg'=>'日期格式不正确!'
            ]);
        }
//        var_dump($date);
//        var_dump(strtotime($date));
//        var_dump(date('Y',strtotime($date)));
        $amount = 0;
        $direct = 0;
        $proxy = 0;
        $reward = 0;
        $data = BrokerageLog::where('proxy_id','=',$uid)->where('state','=',0)->whereYear('created_at',$date[0])->whereMonth('created_at', $date[1])->get();
        if (!empty($data)){
            foreach ($data as $datum){
                $amount+=$datum->brokerage;
                if ($datum->type==1){
                    $direct+=$datum->brokerage;
                }
                if ($datum->type==2){
                    $proxy+=$datum->brokerage;
                }
                if ($datum->type==3){
                    $reward+=$datum->brokerage;
                }
            }
        }
//        dd($db->get());
        return response()->json([
            'msg'=>'ok',
            'data'=>[
                'amount'=>$amount,
                'direct'=>$direct,
                'proxy'=>$proxy,
                'reward'=>$reward
            ]
        ]);
    }
    public function getAssesses()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $count = Assess::count();
        $assesses = Assess::limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        if (!empty($assesses)){
            foreach ($assesses as $assess){
                $assess->loan = Loan::find($assess->loan_id);
                $assess->user = WeChatUser::find($assess->loan->user_id);
            }
        }
        return response()->json([
            'msg'=>'ok',
            'count'=>$count,
            'data'=>$assesses
        ]);
    }

}
