<?php
$root = dirname(__DIR__);

require $root . '/config/session_db.php'; // session disimpan di database (lihat langkah 3)

$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($path === '/' || $path === '') {
    $path = '/landingpage.php';
}

$file = realpath($root . $path);

// Hanya izinkan file .php di dalam project, dan blokir folder internal
$blocked = ['/config/', '/api/', '/logs/'];
$deny = $file === false
    || strpos($file, $root . DIRECTORY_SEPARATOR) !== 0
    || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php';
foreach ($blocked as $b) {
    if (strpos($path, $b) === 0) $deny = true;
}

if ($deny) {
    http_response_code(404);
    exit('404 Not Found');
}

$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $path;
chdir(dirname($file)); // supaya require '../config/...' seperti di XAMPP tetap jalan
require $file;