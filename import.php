<?php

$redis = new Redis();
$redis->connect('10.11.12.13', 6379);

$dump = file_get_contents(__DIR__ . '/filename.json');
$data = json_decode($dump, true);

foreach ($data as $key=>$value) {
    if($value['type'] === Redis::REDIS_STRING) {

        $redis->set($key, $value['data']);
        if($value['ttl'] !== -1) {
            $redis->expireAt($key, time() + $value['ttl']);
        }

    }else{
        echo 'Not a string for key ' . $key . ', type = ' . $value['type'] . PHP_EOL;
    }
}

