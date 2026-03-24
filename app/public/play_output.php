<?php
require_once __DIR__ . '/bootstrap.php';

$encoded = $_GET['file'] ?? '';
if ($encoded !== '') {
    $path = base64_decode($encoded, true);
    if ($path === false || !is_file($path)) { http_response_code(404); exit; }
    $real = realpath($path);
    $outputRoot = realpath($paths['output'] ?? '');
    if ($real === false || $outputRoot === false) { http_response_code(404); exit; }
    $prefix = str_replace('\\','/', rtrim($outputRoot, DIRECTORY_SEPARATOR)) . '/';
    $realNorm = str_replace('\\','/', $real);
    if (strpos($realNorm, $prefix) !== 0) { http_response_code(403); exit; }
    header('Content-Type: audio/wav');
    header('Content-Length: ' . filesize($real));
    readfile($real);
    exit;
}

$dest = $_SESSION['last_build'] ?? '';
$slot = $_GET['slot'] ?? '';
$n = isset($_GET['n']) ? (int)$_GET['n'] : 0;
if ($dest === '' || $slot === '' || $n < 1) { http_response_code(404); exit; }
$realDest = realpath($dest); if ($realDest === false) { http_response_code(404); exit; }
$file = $realDest . DIRECTORY_SEPARATOR . $slot . DIRECTORY_SEPARATOR . $slot . str_pad((string)$n, 2, '0', STR_PAD_LEFT) . '.wav';
$realFile = realpath($file); if ($realFile === false || !is_file($realFile)) { http_response_code(404); exit; }
if (!str_starts_with(str_replace('\\','/', $realFile), str_replace('\\','/', rtrim($realDest, DIRECTORY_SEPARATOR)) . '/')) { http_response_code(403); exit; }
header('Content-Type: audio/wav');
header('Content-Length: ' . filesize($realFile));
readfile($realFile);
exit;
?>