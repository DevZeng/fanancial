<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
