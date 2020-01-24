<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeadAgent extends Model
{
    protected $table = 'lead_agents';

    public function user(){
        return $this->belongsTo(User::class);
    }

}
