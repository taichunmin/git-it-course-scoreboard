<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Redis;

class DockerCloudPortsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dc:portsupdate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use DockerCloud API to update ports.';

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
            'stack' => getenv('DOCKERCLOUD_STACK_NAME') ?: '',
            'username' => getenv('DOCKERCLOUD_USERNAME') ?: '',
            'apikey' => getenv('DOCKERCLOUD_APIKEY') ?: '',
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

        // 取得 stack 資料
        $stacks = $this->dockercloud_curl('/api/app/v1/stack/', ['name'=>$cfg['stack']]);
        // var_export($stacks);

        // 取得所有 client 的 container
        $containers = [];
        foreach($stacks['objects'] as $stack)
            foreach($stack['services'] as $serviceURI) {
                $service = $this->dockercloud_curl($serviceURI);
                if(!preg_match('/^client/us', $service['name']))
                    continue;
                foreach($service['containers'] as $containerURI)
                    $containers[] = $containerURI;
            }
        // var_export($containers);

        $ports = [];
        foreach($containers as $containerURI) {
            $container = $this->dockercloud_curl($containerURI);
            foreach($container['container_ports'] as $container_port)
                if($container_port['inner_port'] === 22)
                    $ports[ $container['name'] ] = $container_port['outer_port'];
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
        if(empty($cfg['stack']) || empty($cfg['username']) || empty($cfg['apikey'])) {
            $this->error('Docker cfg error: '.json_encode($cfg));
            return false;
        }
        return true;
    }

    private function dockercloud_curl($path, $get = [])
    {
        $cfg = &$this->cfg;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://cloud.docker.com$path?" . http_build_query($get),
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => "{$cfg['username']}:{$cfg['apikey']}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CAINFO => base_path('config/cacert.pem'),
        ]);
        $html = curl_exec($ch);
        if(false === $html)
            throw new Exception("Faild -> https://cloud.docker.com$path?" . http_build_query($get).' '.curl_error($ch));
        return json_decode($html, true) ?: $html;
    }
}
