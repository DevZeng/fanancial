<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/5/2
 * Time: 下午2:30
 */
if (!function_exists('createNonceStr')){
    function CreateNonceStr($length = 10){
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}
if (!function_exists('setUserToken')){
    function setUserToken($key,$value)
    {
        \Illuminate\Support\Facades\Redis::set($key,$value);
        \Illuminate\Support\Facades\Redis::expire($key,900);
    }
}
if (!function_exists('getUserToken')) {
    function getUserToken($key)
    {
        $uid = \Illuminate\Support\Facades\Redis::get($key);
        \Illuminate\Support\Facades\Redis::expire($key,900);
        if (!isset($uid)){
            return false;
        }
        return $uid;
    }
}