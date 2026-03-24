<?php
require_once __DIR__ . '/bootstrap.php';
$encoded=$_GET['file']??''; if($encoded===''){ http_response_code(404); exit; }
$path=base64_decode($encoded,true); if($path===false||!is_file($path)){ http_response_code(404); exit; }
$real=realpath($path); if($real===false){ http_response_code(404); exit; }
$allowed=[realpath($paths['scan'])]; if(isset($_SESSION['analysis']['root'])) $allowed[]=realpath($_SESSION['analysis']['root']);
$ok=false; foreach($allowed as $root){ if($root!==false && str_starts_with(str_replace('\\','/',$real), str_replace('\\','/',rtrim($root,DIRECTORY_SEPARATOR)).'/')){$ok=true; break;} }
if(!$ok){ http_response_code(403); exit; }
header('Content-Type: audio/wav'); header('Content-Length: '.filesize($real)); readfile($real); exit;
?>