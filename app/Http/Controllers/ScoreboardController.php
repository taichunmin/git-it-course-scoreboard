<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redis;

class ScoreboardController extends Controller
{
    public function completedUpdate(Request $request)
    {
        $mid = $request->input('mid', ''); // machine id
        $name = $request->input('name', $mid); // user name
        $completed = $request->input('completed', '[]'); // user completed
        $completed = json_decode($completed, true) ?: [];

        if(!empty($mid))
            Redis::pipeline(function ($pipe) use ($mid, $name, $completed) {
                $pipe->hmset('mid:'.$mid, [
                    'mid' => $mid,
                    'name' => $name,
                    'completed' => json_encode($completed),
                ]);
                $pipe->sadd('mid_set', $mid);
            });

        return json_encode(compact('mid', 'name', 'completed'));
    }
}
