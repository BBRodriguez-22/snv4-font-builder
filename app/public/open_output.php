<?php
require_once __DIR__ . '/bootstrap.php';

$dest = $_SESSION['last_build'] ?? ($paths['output'] ?? '');
$real = realpath($dest);
if ($real === false) {
    header('Location: index.php');
    exit;
}

if (PHP_OS_FAMILY === 'Windows') {
    pclose(popen('start "" ' . escapeshellarg($real), 'r'));
} elseif (PHP_OS_FAMILY === 'Darwin') {
    exec('open ' . escapeshellarg($real) . ' > /dev/null 2>&1 &');
} else {
    exec('xdg-open ' . escapeshellarg($real) . ' > /dev/null 2>&1 &');
}

if (isset($_GET['stay'])) { http_response_code(204); exit; }
header('Location: build.php');
exit;
?>