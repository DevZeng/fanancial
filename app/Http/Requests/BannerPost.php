<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerPost extends FormRequest
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
            'url'=>'required'
        ];
    }
    public function messages()
    {
        return [
            'url.required'=>'URL不能为空！'
        ];
//        return parent::messages(); // TODO: Change the autogenerated stub
    }
}