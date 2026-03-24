<?php
require_once __DIR__ . '/bootstrap.php';
$jobs = $paths['jobBase'] ?? '';
if ($jobs !== '' && is_dir($jobs)) {
    foreach (scandir($jobs) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $jobs . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) rrmdir_local($path);
        else @unlink($path);
    }
}
header('Location: index.php');
exit;
?>