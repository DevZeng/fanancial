<?php
/**
 * Created by PhpStorm.
 * User: devzeng
 * Date: 17-8-8
 * Time: 上午11:54
 */

namespace App\Libraries;


use Mockery\Exception;

class WxNotify
{
    private $appId;
    private $appSecret;
    private $accessToken;
    private $getAccessUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
    private $sendUrl = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=%s';
    public function __construct($appId,$appSecret)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }
    public function setAccessToken()
    {
        $url = sprintf($this->getAccessUrl,$this->appId,$this->appSecret);
        $data = $this->httpRequest($url);
        if (!empty($data['access_token'])){
            $this->accessToken = $data['access_token'];
        }else{
            throw new Exception($data);
        }
    }
    public function send($data)
    {
        $url = sprintf($this->sendUrl,$this->accessToken);
        $data = $this->httpRequest($url,$data);
        return $data;
    }
    public function httpRequest($url,$data=null)
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
}