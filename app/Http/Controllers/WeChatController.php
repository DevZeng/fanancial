<?php

namespace App\Http\Controllers;

use App\Libraries\Wxxcx;
use Illuminate\Http\Request;

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
        $url2 = 'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN';
        var_dump($data);
        $url2 = sprintf($url2,$data['access_token'],$data['openid']);
        echo $url2;
        $userData = $wechat->request($url2);
        dd($userData);
    }
}
