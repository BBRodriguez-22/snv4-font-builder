<?php
require_once __DIR__ . '/bootstrap.php';

$selected = $_SESSION['selected_root'] ?? null;
$analysis = $_SESSION['analysis'] ?? null;
if (!$selected || !$analysis) {
    header('Location: index.php');
    exit;
}

$mergeChoices = $_POST['merge'] ?? [];
$manualAssignments = $_POST['manual'] ?? [];

if (isset($_POST['row_src']) && is_array($_POST['row_src'])) {
    $core = editor_core_from_post($_POST, $converter->slots);
} else {
    $plan = $converter->buildPlan($analysis, $mergeChoices, $manualAssignments);
    $core = $plan['core'];
}

$outputName = trim((string)($_POST['output_name'] ?? ($selected['name'] . '_SNV4')));
$result = $converter->buildOutput($outputName, $core);
$counts = $converter->compactCounts($core);
$_SESSION['last_build'] = $result['dest'];

$deletedTemp = false;
if (isset($_POST['delete_temp']) && ($_SESSION['source_mode'] ?? 'scan') === 'zip') {
    $jobId = $_SESSION['job_id'] ?? '';
    if ($jobId !== '') {
        $jobRoot = $paths['jobBase'] . DIRECTORY_SEPARATOR . $jobId;
        if (is_dir($jobRoot)) {
            rrmdir_local($jobRoot);
            $deletedTemp = true;
        }
    }
}

$steps = step_state('Build');
$openOutput = isset($_POST['open_output']);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>SNV4 Font Builder - Build</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="shell">
    <div class="topbar">
        <div class="brand">
            <h1>SNV4 Font Builder <span class="version-tag"><?=h(app_version())?></span></h1>
            <p>Build complete. Final output is in the main output folder.</p>
        </div>
        <div class="actions">
            <a class="btn-secondary" href="index.php">Convert Another</a>
        </div>
    </div>

    <div class="steps">
        <?php foreach($steps as $step): ?>
            <div class="step <?=h($step['state'])?>"><?=h($step['label'])?></div>
        <?php endforeach; ?>
    </div>

    <div class="complete-banner">✅ Conversion complete. Your SNV4 font was built successfully.<div class="success-sub">You can open the output folder, download the built ZIP, or inspect details below.</div></div>
    <div class="notice" style="margin-top:12px;">Built output folder: <span class="code"><?=h($result['dest'])?></span></div>
    <?php if ($deletedTemp): ?>
        <div class="notice" style="margin-top:12px;">Temporary imported source files were deleted.</div>
    <?php endif; ?>

    <div class="grid two" style="margin-top:18px;">
        <div class="card">
            <h2>Build Summary</h2>
            <div class="summary-grid">
                <?php foreach($counts as $slot=>$count): ?>
                    <div class="metric">
                        <span class="small"><?=h($converter->slots[$slot])?></span>
                        <strong><?=h((string)$count)?></strong>
                        <div class="small"><?=h($slot)?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card">
            <h2>Result</h2>
            <div class="empty">Output folder: <span class="code"><?=h($result['name'])?></span><br>Log file: <span class="code"><?=h($result['log'])?></span></div>
            <div class="link-row" style="margin-top:12px;"><button class="btn-secondary" type="button" onclick="window.location='open_output.php'">Open Output Folder</button><a class="btn-secondary" href="download_output.php">Download Built ZIP</a><a class="btn-secondary" href="#" onclick="document.getElementById('build-log-box').style.display=(document.getElementById('build-log-box').style.display==='none'?'block':'none');return false;">Show Build Log</a></div>
        </div>
            <div id="build-log-box" class="log-box" style="display:none; margin-top:12px;"><?php if (is_file($result['log'])) { echo h(file_get_contents($result['log'])); } else { echo h("No build log found."); } ?></div>
        </div>
    </div>

    <div class="card" style="margin-top:18px;">
        <h2>Final Output Preview</h2>
        <table class="table-list">
            <thead><tr><th>Slot</th><th>Files</th></tr></thead>
            <tbody>
            <?php foreach($core as $slot=>$items): ?>
                <?php if(!$items) continue; ?>
                <tr>
                    <td><strong><?=h($slot)?></strong></td>
                    <td>
                        <?php foreach($items as $i=>$item): $builtName = $slot . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) . '.wav'; $builtFull = $result['dest'] . DIRECTORY_SEPARATOR . $slot . DIRECTORY_SEPARATOR . $builtName; ?>
                            <div class="audio-row">
                                <div><?=h($builtName)?></div>
                                <button class="btn-ghost" type="button" onclick="playPreview('play_output.php?file=<?=rawurlencode(base64_encode($builtFull))?>','<?=h($builtName)?>')">Play</button>
                            </div>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer-row">
            <a class="btn-secondary" href="review.php">Back to Review</a>
            <a class="btn" href="index.php">Start New Conversion</a>
        </div>
    </div>
</div>

<div class="playerbar">
    <div class="playerpanel">
        <div class="trackname" id="now-playing">No preview playing</div>
        <div class="actions"><button class="btn-ghost" type="button" onclick="stopPreview()">Stop</button></div>
        <audio id="global-audio" controls preload="none"></audio>
    </div>
</div>
<script src="assets/app.js"></script>
<?php if ($openOutput): ?>
<script>window.addEventListener('load', function(){ fetch('open_output.php?stay=1'); });</script>
<?php endif; ?>
</body>
</html>