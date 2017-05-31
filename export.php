<?php
ini_set('memory_limit', '512M');
set_time_limit(0);
error_reporting(E_ALL);
ini_set("display_errors", 1);

chdir(__DIR__);
$baseName = "redis-dump";

$start = time();

if (file_exists("$baseName.tar.gz")) {
    die("File $baseName.tar.gz already exists !\n");
}
$dir = __DIR__ . "/$baseName";
if (file_exists($dir)) {
    die("Error: directory already exists: " . $dir . PHP_EOL);
}
mkdir($dir);

$redis = new Redis();
$redis->connect('172.17.0.2', 6379);
$keys = $redis->keys('PHPREDIS_SESSION:*');
$count = count($keys);
echo "$count keys\n";

$j = 0;
$fileCount = 0;
$result = [];
foreach ($keys as $i => $key) {
    $result[$key] = [
        'data' => $redis->get($key),
        'type' => $redis->type($key),
        'ttl' => $redis->ttl($key),
    ];

    $j++;
    if ($j >= 10000) {
        $j = 0;
        $dump = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($dir . '/part_' . $fileCount++ . '.json', $dump);
        $result = [];
        echo "Read " . ($i + 1) . " from $count\n";
    }
}
if (!empty($result)) {
    $dump = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    file_put_contents($dir . '/part_' . $fileCount++ . '.json', $dump);
}

exec("tar -czf $baseName.tar.gz $baseName");
exec("rm -r '$dir'");
echo "Complete. Result file: " . __DIR__ ."/" . $baseName . ".tar.gz\n";

$duration = time() - $start;
echo "Elapsed " . (int)($duration / 60) . " minutes " . ($duration % 60) . " seconds\n";
