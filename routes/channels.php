<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Matcher;

//Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//    return (int) $user->id === (int) $id;
//});

Broadcast::channel('match.{matchId}', function ($user, $matchId) {
    $match = Matcher::where('id', $matchId)
        ->where(function ($query) use ($user) {
            $query->where('user1_id', $user->id)
                ->orWhere('user2_id', $user->id);
        })
        ->first();

    if ($match) {
        return [
            'id' => $user->id,
            'name' => $user->first_name,
        ];
    }
    return false;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
