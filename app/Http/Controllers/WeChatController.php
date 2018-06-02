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
        $wechat = new Wxxcx();
        $data = $wechat->request();
        dd($data);
    }
}
