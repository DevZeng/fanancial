<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    //
    public function types()
    {
        return $this->hasMany('App\Models\TypeList','business_id','id');
    }
}
