<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginPost;
use App\Libraries\Wxxcx;
use App\Models\BrokerageLog;
use App\Models\Loan;
use App\Models\Message;
use App\Models\Permission;
use App\Models\ProxyApply;
use App\Models\ProxyRatio;
use App\Models\Rate;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\RoleUser;
use App\Models\ScanRecord;
use App\Models\SysConfig;
use App\Models\WeChatUser;
use App\Models\WithdrawApply;
use App\User;
use GuzzleHttp\Handler\CurlFactory;
use GuzzleHttp\Handler\CurlHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class UserController extends Controller
{
    //后台登录
    //后台登录
    public function login(LoginPost $post)
    {
        $username = $post->username;
        $password = $post->password;
        if (Auth::attempt(['username'=>$username,'password'=>$password],true)){
            $role = RoleUser::where('user_id','=',Auth::id())->pluck('role_id')->first();
            $idArr = RolePermission::where('role_id','=',$role)->pluck('permission_id')->toArray();
            $permissions = Permission::whereIn('id',$idArr)->get();
            return response()->json([
                'msg'=>'ok',
                'data'=>[
                    'token'=>csrf_token(),
                    'permissions'=>$permissions,
                    'name'=>Auth::user()->username
                ]
            ]);
        }
        return response()->json([
            'msg'=>'用户名或密码错误！'
        ],422);
    }
    public function roles()
    {
        $search = Input::get('search');
        if ($search){
            $count = Role::where('display_name','like','%'.$search.'%')->count();
            $roles = Role::where('display_name','like','%'.$search.'%')->get();
        }else{
            $count = Role::count();
            $roles = Role::all();
        }

        return response()->json([
            'msg'=>'ok',
            'count'=>$count,
            'data'=>$roles
        ]);
    }
    //后台退出
    public function logout()
    {
        Auth::logout();
        return response()->json([
            'msg'=>'ok'
        ]);
    }
    public function WXLogin(Request $post)
    {
        $wxxcx = new Wxxcx(config('wxxcx.app_id'),config('wxxcx.app_secret'));
        $sessionKey = $wxxcx->getSessionKey($post->code);
        if ($sessionKey){
            $userData = $wxxcx->decode($post->encryptedData,$post->iv);
            if (isset($userData['code'])&&$userData['code']==10001) {
                $userData = $wxxcx->decode($post->encryptedData,$post->iv);
            }
//            var_dump($userData);
            $userData = json_decode($userData);
            $user = WechatUser::where('open_id','=',$userData->openId)->first();
            if (empty($user)){
                $user = new WeChatUser();
                $user->open_id = $userData->openId;
                $user->nickname = $userData->nickName;
                $user->avatarUrl = $userData->avatarUrl;
                $user->save();
                $token = CreateNonceStr(10);
                setUserToken($token,$user->id);
            }else{
//                $user->flag = 1;
//                $user->save();
                $token = CreateNonceStr(10);
                setUserToken($token,$user->id);
            }
            return response()->json([
                'msg'=>'ok',
                'data'=>[
                    'token'=>$token,
                    'user'=>$user
                ]
            ]);
        }
        return response()->json([
            'msg'=>'ERROR',
            'message'=>$sessionKey
        ],400);
    }
    public function getInfo(Request $post)
    {
        $uid = getUserToken($post->token);
        $user = WeChatUser::findOrFail($uid);
        return response()->json([
            'msg'=>'ok',
            'data'=>[
                'name'=>$user->name,
                'phone'=>$user->phone,
                'sex'=>$user->sex
            ]
        ]);
    }
    public function setInfo(Request $post)
    {
        $uid = getUserToken($post->token);
        $user = WeChatUser::findOrFail($uid);
        $user->name = $post->name?$post->name:$user->name;
        $user->phone = $post->phone?$post->phone:$user->phone;
        $user->sex = $post->sex?$post->sex:$user->sex;
//        dd( $post);
        if ($user->save()){
            return response()->json([
                'msg'=>'ok'
            ]);
        }
    }
    public function applyProxy(Request $post)
    {
        $uid = getUserToken($post->token);
        $count = ProxyApply::where('user_id','=',$uid)->count();
        if ($count>0){
            return response()->json([
                'msg'=>'有待审核的申请！'
            ],422);
        }
        $apply = new ProxyApply();
        $apply->user_id = $uid;
        $apply->name = $post->name;
        $apply->phone = $post->phone;
        $apply->bank = $post->bank;
        $apply->account = $post->account;
        $apply->code = $post->code;
        $apply->save();
        return response()->json([
            'msg'=>'ok'
        ]);
    }
    public function getApply()
    {
        $uid = getUserToken(Input::get('id'));
        $config = SysConfig::first();
        $proxy = ProxyApply::where('user_id','=',$uid)->where('code','=',$config->levelBCode)->orWhere('code','=',$config->levelCCode)->orderBy('id','DESC')->first();
        return response()->json([
            'msg'=>'ok',
            'data'=>$proxy
        ]);
    }
    public function listUsers()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $dbObj = WeChatUser::where('level','=','D');
        $name = Input::get('search');
        if ($name){
            $dbObj->where('nickname','like','%'.$name.'%')->orWhere('phone','like','%'.$name.'%');
//            $count = $dbObj->count();
        }
        $count = $dbObj->count();
        $data = $dbObj->limit($limit)->offset(($page-1)*$limit)->get();
        return response()->json([
            'msg'=>'ok',
            'count'=>$count,
            'data'=>$data
        ]);
    }
    public function listAgents()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $dbObj = WeChatUser::where('level','!=','D');
        $count = $dbObj->count();
        $name = Input::get('search');
        if ($name){
            $dbObj->where('nickname','like','%'.$name.'%')->orWhere('phone','like','%'.$name.'%');
            $count = $dbObj->count();
        }
        $data = $dbObj->limit($limit)->offset(($page-1)*$limit)->get();
        foreach ($data as $datum){
            $datum->level = $datum->level == 'A'?'B':$datum->level;
            $datum->registerCount = WeChatUser::where('proxy_id','=',$datum->id)->count();
            $datum->loanCount = Loan::where('proxy_id','=',$datum->id)->where('state','=',3)->count();
            $datum->loanPersonCount = count(Loan::where('proxy_id','=',$datum->id)->where('state','=',3)->groupBy('user_id')->pluck('user_id'));
            $datum->loanSum = Loan::where('proxy_id','=',$datum->id)->where('state','=',3)->sum('price');
            $datum->brokerage = BrokerageLog::where('user_id','=',$datum->id)->sum('brokerage');
            $datum->pay = BrokerageLog::where('user_id','=',$datum->id)->where('state','=',1)->sum('brokerage');
            $datum->need = BrokerageLog::where('user_id','=',$datum->id)->where('state','=',0)->sum('brokerage');
            $datum->bank = ProxyApply::where('state','=',2)->where('user_id','=',$datum->id)->pluck('account')->first();
        }
        return response()->json([
            'msg'=>'ok',
            'count'=>$count,
            'data'=>$data
        ]);
    }
    public function listApplies()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $search = Input::get('search');
        if ($search){
//            dd($search);
            $data = ProxyApply::where('name','like','%'.$search.'%')->orWhere('phone','like','%'.$search.'%')->limit($limit)->offset(($page-1)*$limit)->get();
        }else{
            $data = ProxyApply::limit($limit)->offset(($page-1)*$limit)->get();
        }
        return response()->json([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getAccessToken()
    {
//        $uid = getUserToken(Input::get('token'));
        $uid = 1;
//        $user = WeChatUser::find($uid);
        $wx = new Wxxcx(config('wxxcx.app_id'),config('wxxcx.app_secret'));
        $data = $wx->getAccessToken();
        $curl = new CurlHandler();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$data['access_token'];
        $data = array(
            'path' => 'pages/index/index',
            "width" => 320,
            'scene'=>'proxy='.$uid,
            'auto_color'=>false,
            'line_color'=>'{"r":"0","g":"0","b":"0"}'
        );

        $curl->setHeader('Content-Type', 'application/json');

        $curl->post($url, $data);

        $curl->close();

        return $curl->response;
        $qrcode = $wx->request('https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$data['access_token'],$requestData);
        return response()->make($qrcode,200,['content-type:image/gif']) ;
        dd($qrcode);
        dd($data);
    }
    //获取access_token
    public function get_access_token(){
        $appid = config('wxxcx.app_id');
        $secret = config('wxxcx.app_secret');
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
        return $data = $this->curl_get($url);
    }

    public function curl_get($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return $data;
    }
    //获得二维码
    public function get_qrcode() {
        $uid = getUserToken(Input::get('token'));
        header('content-type:image/gif');
        //header('content-type:image/png');格式自选，不同格式貌似加载速度略有不同，想加载更快可选择jpg
        //header('content-type:image/jpg');
        $data = array();
        $data['scene'] = "proxy=" . $uid;
//        $data['page'] = "pages/index/index";
        $data = json_encode($data);
        $access = json_decode($this->get_access_token(),true);
        $access_token= $access['access_token'];
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
        $da = $this->get_http_array($url,$data);
        //这里强调显示二维码可以直接写该访问路径，同时也可以使用curl保存到本地，详细用法可以加群或者加我扣扣
    }
    public function get_http_array($url,$post_data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //没有这个会自动输出，不用print_r();也会在后面多个1
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        $out = json_decode($output);
        return $out;
    }
    public function checkApply()
    {
        $id = Input::get('id');
        $apply = ProxyApply::findOrFail($id);
        $state = Input::get('state');
        if ($state==1){
            $apply->state = 0;
        }else{
            $apply->state = 2;
            $user = WeChatUser::find($apply->user_id);
            $user->level = 'C';
//            $user->code= uniqid();
            $user->save();
        }
        $apply->save();
        return response()->json([
            'msg'=>'ok'
        ]);
    }
    public function createPermission(Request $post)
    {
        $premission = new Permission();
        $premission->name = $post->name;
        $premission->display_name = $post->display_name;
        $premission->save();
        return response()->json([
            'msg'=>'ok'
        ]);
    }
    public function getPermissions()
    {
        $permissions = Permission::all();
        return response()->json([
            'msg'=>'ok',
            'data'=>$permissions
        ]);
    }
    public function createRole(Request $post)
    {
        $role = new Role();
        $role->name = $post->name;
        $role->display_name = $post->display_name;
        $role->save();
        $lists = $post->lists;
        foreach ($lists as $list){
            $rolePermission = new RolePermission();
            $rolePermission->role_id = $role->id;
            $rolePermission->permission_id = $list;
            $rolePermission->save();
        }
        return response()->json([
            'msg'=>'ok'
        ]);
    }
    public function createUser(Request $post)
    {
        $id = $post->id;
        if ($id){

        }else{
            $user = new User();
            $user->name = $post->name;
            $user->phone = $post->phone;
            $user->username = $post->username;
            $user->password = bcrypt($post->password);
            $user->save();
            if ($post->role_id){
                $roleUser = new RoleUser();
                $roleUser->role_id = $post->role_id;
                $roleUser->user_id = $user->id;
                $roleUser->save();
            }
        }
        return response()->json([
            'msg'=>'ok'
        ]);
    }
//    public function crea
//    public function getProxy($id)
//    {
////        $id = Input::get('id');
//        $user = WeChatUser::find($id);
//        $user->proxy = $user->proxy()->first();
//        $lists = WeChatUser::where('proxy_id','=',$id)->get();
//        return response()->json([
//            'msg'=>'ok',
//            'data'=>[
//                'user'=>$user,
//                'list'=>$lists
//            ]
//        ]);
//    }
    public function scan()
    {
        $id = Input::get('id');
        $uid = getUserToken(Input::get('token'));
        $record = new ScanRecord();
        $record->proxy_id = $id;
        $record->user_id = $uid;
        $record->save();
        return response()->json([
            'msg'=>'ok'
        ]);
    }
    public function myAgents()
    {
        $uid = getUserToken(Input::get('token'));
        $agents = WeChatUser::where('level','!=','D')->where('proxy_id','=',$uid)->get();
        foreach ($agents as $agent){
            $ratio = $agent->ratio()->pluck('ratio')->first();
            $agent->ratio = $ratio?$ratio:0;
//            $proxy = $agent->proxy()->where('state','=',2)->first();
//            $agent->phone = $proxy->phone;
//            $agent->name = $proxy->name;
        }
        return response()->json([
            'msg'=>'ok',
            'data'=>$agents
        ]);
    }
    public function myCode()
    {
        $uid = getUserToken(Input::get('token'));
        $user = WeChatUser::find($uid);
        if($user->level !='B'){
            return response()->json([
                'msg'=>'无权操作～'
            ],400);
        }
        $user->code = uniqid();
        $user->save();
        return response()->json([
            'msg'=>'ok',
            'data'=>$user
        ]);
    }
    public function myMessage()
    {
        $uid = getUserToken(Input::get('token'));
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $wechat = WeChatUser::find($uid);
        $message = Message::where('from','=',$wechat->open_id)->orWhere('receive','=',$wechat->open_id)->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        foreach ($message as $item){
            if ($item->from==$wechat->open_id) {
                $item->from = '你：';
            }
            if ($item->receive==$wechat->open_id){
                $item->receive = '你:';
            }
        }
        return response()->json([
            'msg'=>'ok',
            'data'=>$message
        ]);
    }
    public function searchMessage()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $open_id = Input::get('open_id');
        $message = Message::where('from','=',$open_id)->orWhere('receive','=',$open_id)->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        $count = Message::where('from','=',$open_id)->orWhere('receive','=',$open_id)->count();
        return response()->json([
            'msg'=>'ok',
            'count'=>$count,
            'data'=>$message
        ]);
    }
    public function editRate()
    {
        $id = Input::get('user_id');
        $rate = ProxyRatio::where('user_id','=',$id)->first();
        if (empty($rate)){
            $rate = new ProxyRatio();
            $rate->user_id = $id;
        }
        $rate->ratio = Input::get('rate',$rate->rate);
        $rate->save();
        return response()->json([
            'msg'=>'ok'
        ]);
    }
    public function createWithdrawApply(Request $post)
    {
        $uid = getUserToken($post->token);
        $apply = new WithdrawApply();
        $apply->name = $post->name;
        $apply->bank = $post->bank;
        $apply->account = $post->account;
        $apply->date = $post->date;
        $apply->user_id = $uid;
        if ($apply->save()){
            return response()->json([
                'msg'=>'ok'
            ]);
        }
    }
    public function myData()
    {
        $uid = getUserToken(Input::get('token'));
        $personCount = WeChatUser::where('proxy_id','=',$uid)->count();
        $loanCount = Loan::where('proxy_id','=',$uid)->where('state','=',3)->count();
        $loanSum = Loan::where('proxy_id','=',$uid)->where('state','=',3)->sum('price');
        $loanUserCount = count(Loan::where('proxy_id','=',$uid)->groupBy('user_id')->pluck('user_id'));
        return response()->json([
            'msg'=>'ok',
            'data'=>[
                'personCount'=>$personCount,
                'loanCount'=>$loanCount,
                'loanSum'=>$loanSum,
                'loanUserCount'=>$loanUserCount
            ]
        ]);
    }
    public function userData()
    {
        $date = Input::get('date');
        $limit = Input::get('limit',10);
        $page = Input::get('page',1);
        $count = WeChatUser::where('level','!=','D')->count();
        $proxys = WeChatUser::where('level','!=','D')->limit($limit)->offset(($page-1)*$limit)->get();
        foreach ($proxys as $proxy){
            $proxy->level = $proxy->level == 'A'?'B':$proxy->level;
            $proxy->scanCount = ScanRecord::where('proxy_id','=',$proxy->id)->whereYear('created_at',date('Y',strtotime($date)))->whereMonth('created_at', date('m',strtotime($date)))->count();
            $proxy->registerCount = WeChatUser::where('proxy_id','=',$proxy->id)->whereYear('created_at',date('Y',strtotime($date)))->whereMonth('created_at', date('m',strtotime($date)))->count();
            $proxy->loanCount = Loan::where('proxy_id','=',$proxy->id)->where('state','=',3)->whereYear('created_at',date('Y',strtotime($date)))->whereMonth('created_at', date('m',strtotime($date)))->count();
            $proxy->loanPersonCount = count(Loan::where('proxy_id','=',$proxy->id)->where('state','=',3)->whereYear('created_at',date('Y',strtotime($date)))->whereMonth('created_at', date('m',strtotime($date)))->groupBy('user_id')->pluck('user_id'));
            $proxy->loanSum = Loan::where('proxy_id','=',$proxy->id)->where('state','=',3)->whereYear('created_at',date('Y',strtotime($date)))->whereMonth('created_at', date('m',strtotime($date)))->sum('brokerage');
            $proxy->brokerage = BrokerageLog::where('user_id','=',$proxy->id)->whereYear('created_at',date('Y',strtotime($date)))->whereMonth('created_at', date('m',strtotime($date)))->sum('brokerage');
            $proxy->pay = BrokerageLog::where('user_id','=',$proxy->id)->where('state','=',1)->whereYear('created_at',date('Y',strtotime($date)))->whereMonth('created_at', date('m',strtotime($date)))->sum('brokerage');
            $proxy->need = BrokerageLog::where('user_id','=',$proxy->id)->where('state','=',0)->whereYear('created_at',date('Y',strtotime($date)))->whereMonth('created_at', date('m',strtotime($date)))->sum('brokerage');
        }
        return response()->json([
            'msg'=>'ok',
            'count'=>$count,
            'data'=>$proxys
        ]);
    }
    public function myWithoutRecord()
    {
        $uid = getUserToken(Input::get('token'));
        $limit = Input::get('limit',10);
        $page = Input::get('page',1);
        $lists = WithdrawApply::where('user_id','=',$uid)->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        return response()->json([
            'msg'=>'ok',
            'data'=>$lists
        ]);
    }
    public function listWithoutRecord()
    {
        $limit = Input::get('limit',10);
        $page = Input::get('page',1);
        $date = Input::get('date');
        if ($date){
            $count = WithdrawApply::where('date','=',$date)->count();
            $list = WithdrawApply::where('date','=',$date)->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        }else{
            $count = WithdrawApply::count();
            $list = WithdrawApply::limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        }

        foreach ($list as $item){
            $item->nickname = WeChatUser::find($item->user_id)->nickname;
            $item->name = WeChatUser::find($item->user_id)->name;
            $item->amount = BrokerageLog::where('user_id','=',$item->user_id)->where('state','=',0)->whereYear('created_at',date('Y',strtotime($date)))->whereMonth('created_at', date('m',strtotime($date)))->sum('brokerage');
        }
        return response()->json([
            'msg'=>'ok',
            'count'=>$count,
            'data'=>$list
        ]);
    }
    public function payApply($id)
    {
        $apply = WithdrawApply::find($id);
        $apply->state = 1;
        if ($apply->save()){
            BrokerageLog::where('user_id','=',$apply->user_id)->where('state','=',0)->whereYear('created_at',date('Y',strtotime($apply->date)))->whereMonth('created_at', date('m',strtotime($apply->date)))->update(['state'=>1]);
        }
        return response()->json([
            'msg'=>'ok'
        ]);
    }
    public function listAgentTree()
    {
        $id = Input::get('id');
        $user = WeChatUser::find($id);
        $parent = [WeChatUser::find($user->proxy_id)];
        $sons  = WeChatUser::where('proxy_id','=',$user->id)->get();
        return response()->json([
            'msg'=>'ok',
            'data'=>[
                'parent'=>$parent,
                'sons'=>$sons
            ]
        ]);
    }
    public function listAgentRecord()
    {
//        dd(Input::all());
        $id = Input::get('id');
        $limit = Input::get('limit',10);
        $page = Input::get('page',1);
        $count = BrokerageLog::where('user_id','=',$id)->count();
//        dd($count);
        $record = BrokerageLog::where('user_id','=',$id)->limit($limit)->offset(($page-1)*$limit)->orderBy('id','DESC')->get();
        return response()->json([
            'msg'=>'ok',
            'count'=>$count,
            'data'=>$record
        ]);
    }
    public function listAdmin()
    {
        $search = Input::get('search');
        $limit = Input::get('limit',10);
        $page = Input::get('page',1);
        if ($search){
            $data = User::where('name','like','%'.$search.'%')->limit($limit)->offset(($page-1)*$limit)->get();
            $count = User::where('name','like','%'.$search.'%')->count();
        }else{
            $data = User::limit($limit)->offset(($page-1)*$limit)->get();
            $count = User::count();
        }
        if (!empty($data)){
            foreach ($data as $datum){
                $role = RoleUser::where('user_id','=',$datum->id)->pluck('role_id')->first();
                $datum->role = !empty($role)?Role::find($role)->display_name:'无权限';
            }
        }
        return response()->json([
            'msg'=>'ok',
            'count'=>$count,
            'data'=>$data
        ]);
    }
}
