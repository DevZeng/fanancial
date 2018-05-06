<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginPost;
use App\Libraries\Wxxcx;
use App\Models\ProxyApply;
use App\Models\WeChatUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class UserController extends Controller
{
    //后台登录
    public function login(LoginPost $post)
    {
        $username = $post->username;
        $password = $post->password;
        if (Auth::attempt(['username'=>$username,'password'=>$password],true)){
            return response()->json([
                'msg'=>'ok',
                'data'=>[
                    'token'=>csrf_token()
                ]
            ]);
        }
        return response()->json([
            'msg'=>'用户名或密码错误！'
        ],422);
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
        $proxy = ProxyApply::where('user_id','=',$uid)->orderBy('id','DESC')->first();
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
        $count = $dbObj->count();
        $name = Input::get('search');
        if ($name){
            $dbObj->where('name','like','%'.$name.'%')->where('phone','like','%'.$name.'%');
        }
        $data = $dbObj->limit($limit)->offset(($page-1)*$limit)->get();
        return response()->json([
            'msg'=>'ok',
            'count'=>$count,
            'data'=>$data
        ]);
    }
    public function listAgents()
    {

    }
    public function listApplies()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $search = Input::get('search');
        if ($search){
            $data = ProxyApply::where('name','like',$search)->where('phone','like','%'.$search.'%')->limit($limit)->offset(($page-1)*$limit)->get();
        }else{
            $data = ProxyApply::limit($limit)->offset(($page-1)*$limit)->get();
        }
        return response()->json([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
}
