<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Redis;

class DockerRemotePortsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dr:portsupdate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use DockerRemote API to update ports.';

    /**
     * Dockercloud 設定值
     * @var array
     */
    protected $cfg = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->cfg = [
            'ip' => getenv('DOCKERREMOTE_IP') ?: '',
            'port' => getenv('DOCKERREMOTE_PORT') ?: '2375',
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if( !$this->check_cfg())
            return;
        $cfg = &$this->cfg;

        // 取得 container 資料
        $containers = $this->dockerremote_curl('/containers/json', ['filters'=>'{"label":["role=git-it-client"]}']);
        // var_export($stacks);

        $ports = [];
        foreach($containers as $container) {
            $name = substr($container['Id'], 0, 12);
            foreach($container['Ports'] as $container_port)
                if($container_port['PrivatePort'] == 22)
                    $ports[ $name ] = $container_port['PublicPort'];
        }
        var_export($ports);

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
    }

    private function check_cfg()
    {
        $cfg = &$this->cfg;
        if(empty($cfg['ip']) || empty($cfg['port'])) {
            $this->error('Docker cfg error: '.json_encode($cfg));
            return false;
        }
        return true;
    }

    private function dockerremote_curl($path, $get = [])
    {
        $cfg = &$this->cfg;
        $ch = curl_init();
        $url = "http://{$cfg['ip']}:{$cfg['port']}$path?" . http_build_query($get);
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            // CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            // CURLOPT_USERPWD => "{$cfg['username']}:{$cfg['apikey']}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CAINFO => base_path('config/cacert.pem'),
        ]);
        $html = curl_exec($ch);
        if(false === $html)
            throw new Exception("Faild -> $url ".curl_error($ch));
        return json_decode($html, true) ?: $html;
    }
}
