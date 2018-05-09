<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeChatUser extends Model
{
    //
    public function loans()
    {
        return $this->hasMany('App\Models\Loan','user_id','id');
    }
    public function proxy()
    {
        return $this->hasOne('App\Models\ProxyApply','user_id','id');
    }
}
