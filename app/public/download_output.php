<?php
require_once __DIR__ . '/bootstrap.php';
$dest = $_SESSION['last_build'] ?? '';
$real = realpath($dest);
if ($real === false || !is_dir($real)) { http_response_code(404); exit; }
$tmpDir = $paths['base'] . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'downloads';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);
$zipName = basename($real) . '.zip';
$zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;
@unlink($zipPath);
if (!zip_directory_local($real, $zipPath)) { http_response_code(500); exit; }
header('Content-Type: application/zip');
header('Content-Length: ' . filesize($zipPath));
header('Content-Disposition: attachment; filename="' . basename($zipPath) . '"');
readfile($zipPath);
exit;
?>