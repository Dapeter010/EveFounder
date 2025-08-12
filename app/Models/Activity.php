<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Jenssegers\Agent\Agent;

class Activity extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function addActivity($user, $info, $model, $model_id)
    {
        $agent = new Agent();
        $ip = request()->ip();
        $userAgent = "Type: {$agent->deviceType()}, System: {$agent->device()} {$agent->platform()}, Browser: {$agent->browser()}";

        $user->activities()->create([
            'info'          => $info,
            'ip'            => $ip,
            'user_agent'    => $userAgent,
            'model'         => $model,
            'model_id'      => $model_id
        ]);
    }
}
