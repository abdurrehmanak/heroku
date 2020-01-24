<?php

namespace App;

use App\Observers\LeadObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Lead extends Model
{
    use Notifiable;

    protected $table = 'leads';

    protected static function boot()
    {
        parent::boot();

        static::observe(LeadObserver::class);

    }

    /**
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForMail($notification)
    {
        return $this->client_email;
    }
    public function lead_agent(){
        return $this->belongsTo(LeadAgent::class, 'agent_id');
    }
    public function lead_source(){
        return $this->belongsTo(LeadSource::class, 'source_id');
    }
    public function lead_status(){
        return $this->belongsTo(LeadStatus::class, 'status_id');
    }
    public function client(){
        return $this->belongsTo(User::class, 'client_id');
    }
    public function follow() {
        return $this->hasMany(LeadFollowUp::class);
    }
    public function files() {
        return $this->hasMany(LeadFiles::class);
    }
}
