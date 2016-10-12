<?php
ini_set('memory_limit','512M');

$redis = new Redis();
$redis->connect('192.168.169.170', 6379);
$keys = $redis->keys('SESSION:*');

foreach ($keys as $key) {
    $data = $redis->get($key);
    $type = $redis->type($key);
    $ttl = $redis->ttl($key);

    $result[$key] = [
        'data' => $data,
        'type' => $type,
        'ttl' => $ttl,
    ];
}

$dump = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
file_put_contents(__DIR__ . '/redis-dump-' . time() . '.json', $dump);
