<?php
session_start();
$bundleBase = dirname(__DIR__, 2);
require_once dirname(__DIR__) . '/lib/Converter.php';

function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

function step_state(string $current): array {
    $steps = ['Import','Select','Review','Build'];
    $out = [];
    foreach ($steps as $step) {
        $state = 'todo';
        if ($step === $current) $state = 'current';
        elseif (array_search($step, $steps, true) < array_search($current, $steps, true)) $state = 'done';
        $out[] = ['label'=>$step,'state'=>$state];
    }
    return $out;
}

function bundle_paths(): array {
    $base = dirname(__DIR__, 2);
    $jobId = $_SESSION['job_id'] ?? '';
    $mode = $_SESSION['source_mode'] ?? 'scan';
    $scan = $base . DIRECTORY_SEPARATOR . 'scan';
    $output = $base . DIRECTORY_SEPARATOR . 'output';
    $jobBase = $base . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'jobs';
    if ($mode === 'zip' && $jobId !== '') {
        $src = $jobBase . DIRECTORY_SEPARATOR . $jobId . DIRECTORY_SEPARATOR . 'source';
        if (is_dir($src)) $scan = $src;
    }
    return ['base'=>$base,'scan'=>$scan,'output'=>$output,'jobBase'=>$jobBase];
}

function editor_core_from_post(array $post, array $slots): array {
    $core = [];
    foreach (array_keys($slots) as $slot) $core[$slot] = [];

    $rowSrc = $post['row_src'] ?? [];
    $rowRel = $post['row_rel'] ?? [];
    $rowConf = $post['row_conf'] ?? [];
    $slotChoice = $post['item_slot'] ?? [];
    $orderChoice = $post['item_order'] ?? [];
    $removeChoice = $post['item_remove'] ?? [];
    $duplicateChoice = $post['item_duplicate'] ?? [];

    foreach ($rowSrc as $id => $src) {
        $slot = $slotChoice[$id] ?? '';
        if (!isset($slots[$slot])) continue;
        if (isset($removeChoice[$id])) continue;

        $rel = $rowRel[$id] ?? basename((string)$src);
        $conf = $rowConf[$id] ?? 'edited';
        $order = isset($orderChoice[$id]) ? (float)$orderChoice[$id] : 9999.0;
        $copies = isset($duplicateChoice[$id]) ? 2 : 1;

        for ($i = 0; $i < $copies; $i++) {
            $core[$slot][] = [
                'src' => (string)$src,
                'rel' => (string)$rel,
                'confidence' => (string)$conf,
                'source_group' => 'edited',
                '__order' => $order + ($i * 0.01),
            ];
        }
    }

    foreach ($core as $slot => $items) {
        usort($items, function ($a, $b) {
            $oa = $a['__order'] ?? 9999;
            $ob = $b['__order'] ?? 9999;
            if ($oa === $ob) return strnatcasecmp($a['rel'], $b['rel']);
            return $oa <=> $ob;
        });
        foreach ($items as &$item) unset($item['__order']);
        unset($item);
        $core[$slot] = $items;
    }

    return $core;
}

function preview_rows(array $core): array {
    $rows = [];
    $n = 1;
    foreach ($core as $slot => $items) {
        foreach ($items as $item) {
            $rows[] = [
                'id' => 'r' . $n++,
                'slot' => $slot,
                'src' => $item['src'],
                'rel' => $item['rel'],
                'confidence' => $item['confidence'] ?? 'edited',
            ];
        }
    }
    return $rows;
}

function rrmdir_local(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) rrmdir_local($path);
        else @unlink($path);
    }
    @rmdir($dir);
}

function app_version(): string { return 'v1.2'; }

function zip_directory_local(string $sourceDir, string $zipPath): bool {
    if (!is_dir($sourceDir)) return false;
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return false;
    $sourceReal = realpath($sourceDir);
    if ($sourceReal === false) return false;
    $sourceReal = rtrim(str_replace('\\', '/', $sourceReal), '/');
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!$file->isFile()) continue;
        $full = $file->getPathname();
        $fullReal = realpath($full);
        if ($fullReal === false) continue;
        $fullNorm = str_replace('\\', '/', $fullReal);
        $local = ltrim(substr($fullNorm, strlen($sourceReal)), '/');
        $zip->addFile($fullReal, $local);
    }
    $zip->close();
    return is_file($zipPath);
}

$paths = bundle_paths();
$converter = new Converter($paths['base'], $paths['scan'], $paths['output']);
?>