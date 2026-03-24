<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    http_response_code(413);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Upload Too Large</title><link rel="stylesheet" href="assets/style.css"></head><body><div class="shell"><div class="card"><h2>Upload Too Large</h2><div class="notice">The uploaded ZIP is larger than the current PHP upload limit.</div><p class="small" style="margin-top:12px;">This bundle now includes a custom PHP config for larger uploads. Close the web UI, restart it with start-web.bat, and try again.</p><div class="footer-row"><a class="btn-secondary" href="index.php">Back</a></div></div></div></body></html>';
    exit;
}

if($_SERVER['REQUEST_METHOD']!=='POST' || !isset($_FILES['font_zip'])){ header('Location: index.php'); exit; }
$file=$_FILES['font_zip']; if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK){ header('Location: index.php'); exit; }
if(strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='zip'){ header('Location: index.php'); exit; }
$jobId=date('Ymd_His').'_'.bin2hex(random_bytes(4));
$jobRoot=$paths['jobBase'].DIRECTORY_SEPARATOR.$jobId;
$sourceDir=$jobRoot.DIRECTORY_SEPARATOR.'source'; $outputDir=$jobRoot.DIRECTORY_SEPARATOR.'output';
@mkdir($sourceDir,0777,true); @mkdir($outputDir,0777,true);
$zipPath=$jobRoot.DIRECTORY_SEPARATOR.'upload.zip';
move_uploaded_file($file['tmp_name'],$zipPath);
$zip=new ZipArchive(); if($zip->open($zipPath)===true){ $zip->extractTo($sourceDir); $zip->close(); }
$_SESSION['job_id']=$jobId; $_SESSION['source_mode']='zip'; unset($_SESSION['selected_root'],$_SESSION['analysis'],$_SESSION['last_build']);
header('Location: index.php'); exit;
?>