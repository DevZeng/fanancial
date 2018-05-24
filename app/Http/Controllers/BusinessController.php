<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessPost;
use App\Models\Business;
use App\Models\Type;
use App\Models\TypeList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class BusinessController extends Controller
{
    //
    public function create(BusinessPost $post)
    {
        $id = $post->get('id');
        if ($id){
            $business = Business::findOrFail($id);
        }else{
            $business = new Business();
        }
        $business->name = $post->name?$post->name:$business->name;
        $business->min = $post->min?$post->min:$business->min;
        $business->max = $post->max?$post->max:$business->max;
        $business->promotion = $post->promotion?$post->promotion:$business->promotion;
        $business->sort = $post->sort?$post->sort:1;
        $business->brokerage = $post->brokerage?$post->brokerage:$business->brokerage;
        $business->intro = $post->intro?$post->intro:$business->intro;
        $business->state = $post->state?$post->state:$business->state;
        $business->sort = $post->sort?$post->sort:$business->sort;
        $types = $post->types;
        $business->save();
        if (!empty($types)){
            $business->types()->delete();
            foreach ($types as $type){
                $list = new TypeList();
                $list->type_id = $type;
                $list->business_id = $business->id;
                $list->save();
            }
        }
        return response()->json([
            'msg'=>'ok'
        ]);
    }
    public function delete($id)
    {
        $business = Business::find($id);
        $business->delete();
        return response()->json([
            'msg'=>'ok'
        ]);
    }
    public function getBusiness($id)
    {
        $business = Business::findOrFail($id);
        $typeArr = $business->types()->pluck('type_id')->toArray();
        $business->types = Type::whereIn('id',$typeArr)->get();
        return response()->json([
            'msg'=>'ok',
            'data'=> $business
        ]);
    }
    public function getBusinesses(Request $post)
    {
        $state = $post->get('state',1);
        $page = $post->get('page',1);
        $limit = $post->get('limit',10);
        $business = Business::where('state','=',$state)->limit($limit)->offset(($page-1)*$limit)->get();
        return response()->json([
            'msg'=>'ok',
            'data'=>$business
        ]);
    }

    public function getTypes()
    {
        $types = Type::all();
        return response()->json([
            'msg'=>'ok',
            'data'=>$types
        ]);
    }
    public function Businesses()
    {
        $limit = Input::get('limit',10);
        $page = Input::get('page',1);
        $db = Business::select(['id','promotion'])->where('state','=',1);
        $db2 = Business::select(['id','promotion'])->where('state','=',1);
        $types = Input::get('types');
        $min = Input::get('min');
        $max = Input::get('max');
//        dd(Input::all());
        if ($min){

            $db->whereBetween('min',[$min,$max])->whereBetween('max',[$min,$max]);
//            dd($data1);
            $db2->whereNotBetween('min',[$min,$max])->whereNotBetween('max',[$min,$max]);
//            $data = array_merge($data1,$data2);
        }
        if ($types){
//            print_r($types);
            $types = explode(',',$types);
            $list = TypeList::whereIn('type_id',$types)->pluck('business_id')->toArray();
//            dd($list);
            $db->whereIn('id',$list);
            $db2 = $db->whereNotIn('id',$list);
        }
        $data1 = $db->limit($limit)->offset(($page-1)*$limit)->get()->toArray();
        $data2 = $db2->limit($limit)->offset(($page-1)*$limit)->get()->toArray();
        $data = array_merge($data1,$data2);
        return response()->json([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
}
