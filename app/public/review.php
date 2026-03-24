<?php
require_once __DIR__ . '/bootstrap.php';

$path = $_POST['path'] ?? ($_SESSION['selected_root']['path'] ?? '');
if ($path === '' || !is_dir($path)) {
    header('Location: index.php');
    exit;
}

$selected = ['name'=>basename($path),'path'=>$path,'display'=>basename($path),'wav_count'=>0,'score'=>0];
foreach ($converter->findCandidateRoots((bool)($_SESSION['include_extras'] ?? false)) as $candidate) {
    if ($candidate['path'] === $path) { $selected = $candidate; break; }
}

$analysis = $converter->analyzeRoot($path);
$_SESSION['selected_root'] = $selected;
$_SESSION['analysis'] = $analysis;

$mergeChoices = $_POST['merge'] ?? [];
$manualAssignments = $_POST['manual'] ?? [];
$advanced = isset($_POST['advanced']) ? ($_POST['advanced'] === '1') : false;
if (isset($_POST['open_advanced'])) $advanced = true;
if (isset($_POST['close_advanced'])) $advanced = false;
$_SESSION['advanced_edit'] = $advanced;

$basePlan = $converter->buildPlan($analysis, $mergeChoices, $manualAssignments);
if ($advanced && isset($_POST['row_src']) && is_array($_POST['row_src'])) {
    $previewCore = editor_core_from_post($_POST, $converter->slots);
} else {
    $previewCore = $basePlan['core'];
}

$mergeOptions = [];
foreach (['drag','lock','melt'] as $slot) {
    $begin = []; $base = [];
    foreach ($analysis['core'][$slot] as $item) {
        $g = strtolower($item['source_group']);
        if ($slot === 'drag' && in_array($g, ['bgndrag','begindrag'], true)) $begin[] = $item;
        elseif ($slot === 'lock' && in_array($g, ['bgnlock','beginlock'], true)) $begin[] = $item;
        elseif ($slot === 'melt' && in_array($g, ['bgnmelt','beginmelt'], true)) $begin[] = $item;
        else $base[] = $item;
    }
    if ($begin && $base) $mergeOptions[$slot] = ['begin'=>$begin,'base'=>$base];
}

$steps = step_state('Review');
$rows = preview_rows($previewCore);
$defaultOpenOutput = (($_SESSION['source_mode'] ?? 'scan') === 'zip');
$defaultDeleteTemp = (($_SESSION['source_mode'] ?? 'scan') === 'zip');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>SNV4 Font Builder - Review</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="shell">
    <div class="topbar">
        <div class="brand">
            <h1>SNV4 Font Builder <span class="version-tag"><?=h(app_version())?></span></h1>
            <p>Quick review first. Open advanced edit only when needed.</p>
        </div>
        <div class="actions">
            <a class="btn-secondary" href="index.php">Back</a>
        </div>
    </div>

    <div class="steps">
        <?php foreach($steps as $step): ?>
            <div class="step <?=h($step['state'])?>"><?=h($step['label'])?></div>
        <?php endforeach; ?>
    </div>

    <form method="post" action="review.php">
        <input type="hidden" name="path" value="<?=h($path)?>">
        <input type="hidden" name="advanced" value="<?=$advanced ? '1' : '0'?>">

        <div class="grid two">
            <div class="card">
                <h2>Selected Root</h2>
                <div class="candidate">
                    <div>
                        <strong><?=h($selected['name'])?></strong>
                        <div class="small"><?=h($selected['display'])?></div>
                        <div style="margin-top:8px;">
                            <span class="pill">WAV <?=h((string)$selected['wav_count'])?></span>
                            <span class="pill">Score <?=h((string)$selected['score'])?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <h2>Scan Summary</h2>
                <div class="summary-grid">
                    <div class="metric"><span class="small">Auto matched</span><strong><?=array_sum(array_map('count', $analysis['core']))?></strong></div>
                    <div class="metric"><span class="small">Recognized extras</span><strong><?=count($analysis['extras'])?></strong></div>
                    <div class="metric"><span class="small">Ignored special</span><strong><?=count($analysis['ignored'])?></strong></div>
                    <div class="metric"><span class="small">Unmatched</span><strong><?=count($analysis['unmatched'])?></strong></div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:18px;">
            <h2>Merge Decisions</h2>
            <?php if(!$mergeOptions): ?>
                <div class="empty">No duplicate begin/base source groups detected for drag, lock, or melt.</div>
            <?php else: ?>
                <div class="merge-grid">
                    <?php foreach($mergeOptions as $slot => $groups): ?>
                        <div class="choice">
                            <strong><?=h($converter->slots[$slot])?></strong>
                            <div class="small" style="margin:4px 0 10px;">Choose how to build this slot and preview both groups below.</div>
                            <div>
                                <label><input type="radio" name="merge[<?=h($slot)?>]" value="merge" <?=(!isset($mergeChoices[$slot]) || $mergeChoices[$slot] === 'merge') ? 'checked' : ''?>> Merge both</label>
                                <label><input type="radio" name="merge[<?=h($slot)?>]" value="begin" <?=(($mergeChoices[$slot] ?? '') === 'begin') ? 'checked' : ''?>> Begin group only</label>
                                <label><input type="radio" name="merge[<?=h($slot)?>]" value="base" <?=(($mergeChoices[$slot] ?? '') === 'base') ? 'checked' : ''?>> Base group only</label>
                            </div>

                            <div class="equal-split" style="margin-top:12px;">
                                <div class="card">
                                    <h2 style="font-size:16px;">Begin Group</h2>
                                    <?php foreach($groups['begin'] as $audioItem): ?>
                                        <div class="audio-row">
                                            <div><?=h($audioItem['rel'])?></div>
                                            <button class="btn-ghost" type="button" onclick="playPreview('play.php?file=<?=rawurlencode(base64_encode($audioItem['src']))?>','<?=h($audioItem['rel'])?>')">Play</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="card">
                                    <h2 style="font-size:16px;">Base Group</h2>
                                    <?php foreach($groups['base'] as $audioItem): ?>
                                        <div class="audio-row">
                                            <div><?=h($audioItem['rel'])?></div>
                                            <button class="btn-ghost" type="button" onclick="playPreview('play.php?file=<?=rawurlencode(base64_encode($audioItem['src']))?>','<?=h($audioItem['rel'])?>')">Play</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-top:18px;">
            <h2>Unmatched Files</h2>
            <?php if(!$analysis['unmatched']): ?>
                <div class="empty">No unmatched files.</div>
            <?php else: ?>
                <table class="table-list">
                    <thead><tr class="drag-row"><th>File</th><th>Preview</th><th>Assign As</th></tr></thead>
                    <tbody class="sortable-body">
                    <?php foreach($analysis['unmatched'] as $item): ?>
                        <tr class="drag-row">
                            <td><?=h($item['rel'])?></td>
                            <td><button class="btn-ghost" type="button" onclick="playPreview('play.php?file=<?=rawurlencode(base64_encode($item['src']))?>','<?=h($item['rel'])?>')">Play</button></td>
                            <td>
                                <select class="name-input" style="padding:10px 12px;" name="manual[<?=h($item['src'])?>]">
                                    <option value="skip" <?=($manualAssignments[$item['src']] ?? 'skip') === 'skip' ? 'selected' : ''?>>Skip</option>
                                    <?php foreach($converter->slots as $key => $label): ?>
                                        <option value="<?=h($key)?>" <?=($manualAssignments[$item['src']] ?? '') === $key ? 'selected' : ''?>><?=h($label)?> [<?=h($key)?>]</option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-top:18px;">
            <h2>Build Options</h2>
            <label class="small" for="output_name">Output folder name</label>
            <input class="name-input" id="output_name" name="output_name" value="<?=h($_POST['output_name'] ?? ($selected['name'] . '_SNV4'))?>">
            <div style="margin-top:14px; display:grid; gap:8px;">
                <label><input type="checkbox" name="open_output" value="1" <?=(isset($_POST['open_output']) || $defaultOpenOutput) ? 'checked' : ''?>> Open output folder after build</label>
                <?php if (($_SESSION['source_mode'] ?? 'scan') === 'zip'): ?>
                    <label><input type="checkbox" name="delete_temp" value="1" <?=(isset($_POST['delete_temp']) || $defaultDeleteTemp) ? 'checked' : ''?>> Delete temporary imported source after build</label>
                <?php endif; ?>
            </div>

            <div class="footer-row">
                <?php if (!$advanced): ?>
                    <button class="btn-danger" type="submit" name="open_advanced" value="1">Show Advanced Edit / Full Preview</button>
                <?php else: ?>
                    <button class="btn-secondary" type="submit" name="close_advanced" value="1">Hide Advanced Edit</button>
                    <button class="btn-secondary" type="submit">Refresh Advanced Preview</button>
                <?php endif; ?>
                <button class="btn" type="submit" formaction="build.php">Build Now</button>
            </div>
        </div>

        <?php if ($advanced): ?>
        <div class="card" style="margin-top:18px;">
            <h2>Full Intended Build Preview</h2>
            <div class="small" style="margin-bottom:12px;">Edit the full planned SNV4 output before building. You can move sounds to different slots, drag to reorder within a group, remove sounds, or duplicate a sound once.</div><div class="drag-hint">Tip: drag rows up or down within each slot to reorder them quickly.</div>

            <?php if(!$rows): ?>
                <div class="empty">No planned sounds to preview.</div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach($converter->slots as $slotKey => $slotLabel): ?>
                        <?php $slotRows = array_values(array_filter($rows, fn($r) => $r['slot'] === $slotKey)); if (!$slotRows) continue; ?>
                        <div class="card">
                            <h2><?=h($slotLabel)?> [<?=h($slotKey)?>]</h2>
                            <table class="table-list">
                                <thead>
                                    <tr class="drag-row">
                                        <th>Preview</th>
                                        <th>Source</th>
                                        <th>Target Slot</th>
                                        <th>Order</th>
                                        <th>Duplicate</th>
                                        <th>Remove</th>
                                    </tr>
                                </thead>
                                <tbody class="sortable-body">
                                <?php $pos = 1; foreach($slotRows as $row): ?>
                                    <tr class="drag-row">
                                        <td><button class="btn-ghost" type="button" onclick="playPreview('play.php?file=<?=rawurlencode(base64_encode($row['src']))?>','<?=h($row['rel'])?>')">Play</button></td>
                                        <td>
                                            <strong><?=h($row['rel'])?></strong>
                                            <div class="small"><?=h($row['src'])?></div>
                                            <input type="hidden" name="row_src[<?=h($row['id'])?>]" value="<?=h($row['src'])?>">
                                            <input type="hidden" name="row_rel[<?=h($row['id'])?>]" value="<?=h($row['rel'])?>">
                                            <input type="hidden" name="row_conf[<?=h($row['id'])?>]" value="<?=h($row['confidence'])?>">
                                        </td>
                                        <td>
                                            <select class="name-input" style="padding:10px 12px;" name="item_slot[<?=h($row['id'])?>]">
                                                <?php foreach($converter->slots as $k => $label): ?>
                                                    <option value="<?=h($k)?>" <?=$row['slot'] === $k ? 'selected' : ''?>><?=h($label)?> [<?=h($k)?>]</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td style="width:120px;"><input class="name-input order-input" type="number" min="1" step="1" name="item_order[<?=h($row['id'])?>]" value="<?=h((string)$pos)?>"></td>
                                        <td><input type="checkbox" name="item_duplicate[<?=h($row['id'])?>]" value="1"></td>
                                        <td><input type="checkbox" name="item_remove[<?=h($row['id'])?>]" value="1"></td>
                                    </tr>
                                <?php $pos++; endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </form>
</div>

<div class="playerbar">
    <div class="playerpanel">
        <div class="trackname" id="now-playing">No preview playing</div>
        <div class="actions"><button class="btn-ghost" type="button" onclick="stopPreview()">Stop</button></div>
        <audio id="global-audio" controls preload="none"></audio>
    </div>
</div>
<script src="assets/app.js"></script>
</body>
</html>