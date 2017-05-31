<?php
ini_set('memory_limit','512M');
set_time_limit(0);
error_reporting(E_ALL);
ini_set("display_errors", 1);

chdir(__DIR__);
$baseName = "redis-dump";

$archiveFile = "$baseName.tar.gz";
$dir = __DIR__ . "/$baseName";

$start = time();

if (!file_exists($archiveFile)) {
    die("Error: file $baseName.tar.gz not found !");
}
if (file_exists($dir)) {
    die("Error: directory already exists: " . $dir);
}
exec("tar -xzf " . $archiveFile);
echo "Unpack archive $archiveFile\n";
if (!file_exists($dir)) {
    die("Error: after unpack archive directory with files not found: " . $dir);
}
echo "Start process files in directory " . $dir . PHP_EOL;

$redis = new Redis();
$redis->connect('172.17.0.2', 6379);

$iterator = new \DirectoryIterator($dir);
foreach ($iterator as $fileinfo) {
    if ($fileinfo->getExtension() !== "json") {
        continue;
    }
    echo "Start process file " . $fileinfo->getFilename() . " ...\n";
    $dump = file_get_contents($fileinfo->getPathname());
    $data = json_decode($dump, true);
    foreach ($data as $key => $value) {
        if ($value['type'] === Redis::REDIS_STRING) {
            $redis->set($key, $value['data']);
            if ($value['ttl'] !== -1) {
                $redis->expireAt($key, time() + $value['ttl']);
            }
        } else {
            echo 'Not a string for key ' . $key . ', type = ' . $value['type'] . PHP_EOL;
        }
    }
    echo "Finish process file " . $fileinfo->getFilename() . "\n";
}

exec("rm -r '$dir'");

$duration = time() - $start;
echo "Elapsed " . (int)($duration / 60) . " minutes " . ($duration % 60) . " seconds\n";
