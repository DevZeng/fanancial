<?php

namespace App\Http\Controllers;

use App\Http\Requests\BannerPost;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Mockery\Exception;

class BannerController extends Controller
{
    //
    public function getBanners()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $banners = Banner::orderBy('sort','DESC')->limit($limit)->offset(($page-1)*$limit)->get();
        return response()->json([
            'msg'=>'ok',
            'count'=>Banner::count(),
            'data'=>$banners
        ]);
    }
    public function createBanner(BannerPost $post)
    {
        $banner = new Banner();
        $banner->url = $post->url;
        $banner->sort = $post->sort?$post->sort:1;
        if ($banner->save()){
            return response()->json([
                'msg'=>'ok'
            ]);
        }
    }
    public function delBanner($id)
    {
        $banner = Banner::findOrFail($id);
        if ($banner->delete()){
            return response()->json([
                'msg'=>'ok'
            ]);
        }
    }
    public function editBanner($id,BannerPost $post)
    {
        $banner = Banner::findOrFail($id);
        $banner->url = $post->url;
        $banner->sort = $post->sort?$post->sort:$banner->sort;
        if ($banner->save()){
            return response()->json([
                'msg'=>'ok'
            ]);
        }
    }
    public function getBanner($id)
    {
        $banner = Banner::findOrFail($id);
        return response()->json([
            'msg'=>'ok',
            'data'=>$banner
        ]);
    }
}
