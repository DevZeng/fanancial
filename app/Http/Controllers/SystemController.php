<?php

namespace App\Http\Controllers;

use App\Models\SysConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class SystemController extends Controller
{
    //
    public function getToken()
    {
        return response()->json([
            'msg'=>'ok',
            'data'=>[
                'token'=>csrf_token()
            ]
        ]);
    }
//     public function authorizeForUser($user, $ability, $arguments = [])
//     {
//     }
    public function upload(Request $request)
    {
        if (!$request->hasFile('file')){
            return response()->json([
                'msg'=>'空文件'
            ],422);
        }
        $file = $request->file('file');
        $name = $file->getClientOriginalName();
        $name = explode('.',$name);
        if (count($name)!=2){
            return response()->json([
                'msg'=>'非法文件名!'
            ],422);
        }
        $allow =  [
            'jpg',
            'png',
            'txt',
            'bmp',
            'gif',
            'jpeg',
            'pem',
            'mp4',
        ];
        if (!in_array(strtolower($name[1]),$allow)){
            return response()->json([
                'msg'=>'不支持的文件格式'
            ],422);
        }
        $md5 = md5_file($file);
        $name = $name[1];
        $name = $md5.'.'.$name;
        if (!$file){
            return response()->json([
                'msg'=>'空文件'
            ],422);
        }
//        $count = ProjectPicture::count();
        if ($file->isValid()){
            $destinationPath = 'uploads';
            $file->move($destinationPath,$name);
            return response()->json([
                'msg'=>'ok',
                'data'=>[
//                    'size'=>$count+1,
                    'url'=>$destinationPath.'/'.$name,
                ]
            ]);
        }
    }
    public function getConfig()
    {
        $config = SysConfig::first();
        if (empty($config)){
            $config = new SysConfig();
        }
        return response()->json([
            'msg'=>'ok',
            'data'=>$config
        ]);
    }
    public function editConfig(Request $post)
    {
        $config = SysConfig::first();
        if (empty($config)){
            $config = new SysConfig();
            $config->rate = 60;
            $config->showSelect = 0;
        }
        $config->rate = $post->rate?$post->rate:$config->rate;
        $config->levelBCode = $post->levelBCode?CreateNonceStr(6):$config->levelBCode;
        $config->levelCCode = $post->levelCCode?CreateNonceStr(6):$config->levelCCode;
        $config->showSelect = $post->showSelect?$post->showSelect:0;
//        dd($config);
        if ($config->save()){
            return response()->json([
                'msg'=>'ok'
            ]);
        }
    }
//    public function getConfig(){}
    public function checkSignature()
    {
        $signature = Input::get('signature');
        $timestamp = Input::get('timestamp');
        $nonce =Input::get('nonce');
        $token = "zrdcForDev";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if($tmpStr == $signature){
            return true;
        }else{
            return false;
        }
    }
}
