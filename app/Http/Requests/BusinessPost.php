<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessPost extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
            'id'=>'filled',
            'name'=>'required_without:id',
            'min'=>'required_without:id',
            'max'=>'required_without:id',
            'promotion'=>'required_without:id',
            'brokerage'=>'required_without:id',
            'intro'=>'required_without:id',
            'state'=>'nullable|numeric',
            'sort'=>'nullable|numeric',
        ];
    }
    public function messages()
    {
        return [
          'name.required_without'=>'名称不能为空！',
          'min.required_without'=>'金额不能为空！',
          'max.required_without'=>'金额不能为空！',
          'promotion.required_without'=>'宣传图不能为空！',
          'brokerage.required_without'=>'佣金不能为空！',
          'intro.required_without'=>'介绍不能为空！',
          'state.numeric'=>'参数格式错误！',
          'sort.numeric'=>'参数格式错误！',
          'id.filled'=>'id不能为空!'
        ];
//        return parent::messages(); // TODO: Change the autogenerated stub
    }
}
