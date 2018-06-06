<?php

namespace App\Http\Controllers;

use App\Libraries\Wxxcx;
use App\Models\ProxyApply;
use App\Models\WeChatUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class WeChatController extends Controller
{
    //
    public function getToken(Request $post)
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code';
        $code = $post->code;
        $url = sprintf($url,config('wxxcx.appId'),config('wxxcx.appSecret'),$code);
        $wechat = new Wxxcx(config('wxxcx.app_id'),config('wxxcx.app_secret'));
        $data = $wechat->request($url);
        try{
            $url2 = 'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN';
            $url2 = sprintf($url2,$data['access_token'],$data['openid']);
//        var_dump($data);

//        echo $url2;
            $userData = $wechat->request($url2);
            $user = WeChatUser::where('open_id','=',$userData['openid'])->first();
            if (empty($user)){
                $user = new WeChatUser();
                $user->open_id = $userData['openid'];
                $user->nickname = $userData['nickname'];
                $user->avatarUrl = $userData['headimgurl'];
                $user->code = CreateNonceStr(8);
                if ($post->proxyid){
                    $user->proxy_id = $post->proxyid;
                }
                $user->save();
                $id = $user->id;
                $user = WeChatUser::find($id);
                $user->apply = 0;
                $token = CreateNonceStr(10);
                setUserToken($token,$user->id);
            }else{
//                $user->flag = 1;
//                $user->save();
                $user->apply = ProxyApply::where('user_id','=',$user->id)->where('state','!=',2)->count();
                $user->apply = $user->apply==0?0:1;
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
        }catch (\Exception $exception){
            return response()->json([
                'msg'=>'登录失败！',
                'data'=>$data
            ]);
        }

//        dd($userData);
    }
    public function touch()
    {
        $open_id = Input::get('open_id');
        $user = WeChatUser::where('open_id','=',$open_id)->first();
        if (!empty($user)){
            $user->apply = ProxyApply::where('user_id','=',$user->id)->where('state','!=',2)->count();
            $user->apply = $user->apply==0?0:1;
            $token = CreateNonceStr(10);
            setUserToken($token,$user->id);
            return response()->json([
                'msg'=>'ok',
                'data'=>[
                    'token'=>$token,
                    'user'=>$user
                ]
            ]);
        }else{
            return response()->json([
                'msg'=>'用户不存在！'
            ]);
        }
    }
    public function check(Request $post)
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code';
        $code = $post->code;
        $uid = getUserToken($post->token);
        $url = sprintf($url,config('wxxcx.appId'),config('wxxcx.appSecret'),$code);
        $wechat = new Wxxcx(config('wxxcx.app_id'),config('wxxcx.app_secret'));
        $data = $wechat->request($url);
        if (isset($data['access_token'])){
            $user = WeChatUser::find($uid);
            $requestUri = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=%s&openid=%s&lang=zh_CN';
            $requestUri = sprintf($requestUri,$data['access_token'],$user->open_id);
            $returnData = $wechat->request($requestUri);
        }
        return response()->json($returnData);
        if (isset($returnData)){
            return response()->json([
                'msg'=>'ok',
                'data'=>[
                    'subscribe'=>$returnData['subscribe']?$returnData['subscribe']:0,
                    'returnData'=>$returnData
                ]
            ]);
        }
        return response()->json([
            'msg'=>'ok',
            'data'=>[
                'subscribe'=>0
            ]
        ]);
    }
}
