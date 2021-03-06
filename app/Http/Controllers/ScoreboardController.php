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
            $user['completed'] = empty($user['completed']) ? [] : json_decode($user['completed'], true);
            $user['port'] = empty($user['port']) ? '' : $user['port'];
            $user['cnt'] = count($user['completed']);
            $users[] = $user;
        }

        usort($users, function($a, $b) { // cnt desc, port asc, mid asc
            if ($a['cnt'] != $b['cnt'])
                return $b['cnt'] - $a['cnt'];
            if ($a['port'] != $b['port'])
                return ($a['port'] < $b['port']) ? -1 : ($a['port'] > $b['port']);
            return ($a['mid'] < $b['mid']) ? -1 : ($a['mid'] > $b['mid']);
        });

        return view('scoreboard', compact('users', 'problems'));
    }

    public function completedUpdate(Request $request)
    {
        $mid = $request->input('mid', ''); // machine id

        if(empty($mid))
            return json_encode(['result' => 'mid error']);

        Redis::pipeline(function ($pipe) use ($mid, $request) {
            $completed = $request->input('completed', '[]'); // user completed
            $completed = json_decode($completed, true) ?: [];
            $pipe->hmset('user:'.$mid, [
                'mid' => $mid,
                'name' => $request->input('name', $mid), // name
                'github' => $request->input('github', ''), // github username
                'owned' => $request->input('owned', '0'), // owned
                'completed' => json_encode($completed),
            ]);
            $pipe->sadd('user_mids', $mid);
        });

        return json_encode(['result' => 'ok']);
    }

    public function portsUpdate(Request $request)
    {
        $ports = json_decode($request->input('ports'), true) ?: [];
        if(empty($ports))
            return json_encode(['result' => 'ports error']);
        $noport_mids = array_diff(Redis::smembers('user_mids'), array_keys($ports));

        Redis::pipeline(function ($pipe) use ($ports, $noport_mids) {
            foreach($ports as $mid => $port) {
                $pipe->hmset('user:'.$mid, [
                    'mid' => $mid,
                    'port' => $port, // name
                ]);
                $pipe->sadd('user_mids', $mid);
            }
            foreach($noport_mids as $mid)
                $pipe->hdel('user:'.$mid, ['port']);
        });

        return json_encode(['result' => 'ok']);
    }
}
