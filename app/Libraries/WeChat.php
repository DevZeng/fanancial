<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/6/12
 * Time: 上午9:34
 */
class WeChat
{
    private $appId;
    private $appSecret;
    private $codeToAccessTokenUrl;
    private $userInfoUrl;
    public function __construct($appId,$appSecret,$codeToAccessTokenUrl='https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code')
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->codeToAccessTokenUrl = $codeToAccessTokenUrl;
        $this->userInfoUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN';
    }
    //获取用户信息网页授权
    public function getUserInfo($code)
    {
        $url = sprintf($this->codeToAccessTokenUrl,$this->appId,$this->appSecret,$code);
        $accessToken = $this->request($url);
        try{
            $userInfoUrl = sprintf($this->userInfoUrl,$accessToken['access_token'],$accessToken['openid']);
            $userInfo = $this->request($userInfoUrl);
            return $userInfo;
        }catch (Exception $exception){
            return $exception->getMessage();
        }
    }
    public function request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        if($output === FALSE ){
            return false;
        }
        curl_close($curl);
        return json_decode($output,JSON_UNESCAPED_UNICODE);
    }
    //公众号通知
    public function notify($data)
    {
        $url= 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
        $url = sprintf($url,$this->appId,$this->appSecret);
        $accessToken = $this->request($url);
        if (isset($data['access_token'])){
            $sendUrl = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$accessToken['access_token'];
            $this->request($sendUrl,json_encode($data));
        }
    }
}