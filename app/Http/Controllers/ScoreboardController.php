<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redis;

class ScoreboardController extends Controller
{
    public function show()
    {
        $user_mids = Redis::smembers('user_mids');
        $problems = [
            "GET GIT",
            "REPOSITORY",
            "COMMIT TO IT",
            "GITHUBBIN",
            "REMOTE CONTROL",
            "FORKS AND CLONES",
            "BRANCHES AREN'T JUST FOR BIRDS",
            "IT'S A SMALL WORLD",
            "PULL, NEVER OUT OF DATE",
            "REQUESTING YOU PULL, PLEASE",
            "MERGE TADA!",
        ];

        $users = [];
        foreach($user_mids as $mid) {
            $user = Redis::hgetall('user:'.$mid);
            $user['completed'] = json_decode($user['completed'], true) ?: [];
            $users[] = $user;
        }

        return view('scoreboard', compact('users', 'problems'));
    }

    public function completedUpdate(Request $request)
    {
        $mid = $request->input('mid', ''); // machine id

        if(!empty($mid))
            Redis::pipeline(function ($pipe) use ($mid, $request) {
                $completed = $request->input('completed', '[]'); // user completed
                $completed = json_decode($completed, true) ?: [];
                $pipe->hmset('user:'.$mid, [
                    'mid' => $mid,
                    'name' => $request->input('name', $mid), // name
                    'github' => $request->input('github', ''), // github username
                    'completed' => json_encode($completed),
                ]);
                $pipe->sadd('user_mids', $mid);
            });

        return json_encode(['result' => 'ok']);
    }
}
