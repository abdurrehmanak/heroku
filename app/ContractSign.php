<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContractSign extends Model
{
    public function getSignatureAttribute()
    {
        return asset_url('contract/sign/'.$this->attributes['signature']);
    }
}
