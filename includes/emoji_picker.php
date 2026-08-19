<?php
$pickerId = $pickerId ?? 'msgTpl';
$emojis = ['🔔','⏰','📅','✅','❌','📌','📝','💡','💧','💊','🍽️','🏃','😴','❤️','⭐','🎉','⚠️','🔥','💪','🙏','📍','🎯','✨','🌙','☀️','☕','🧹','📦','💰','🧠'];
$frames = [
    ['label' => lang('create.tpl_f1_label'), 'text' => lang('create.tpl_f1_text')],
    ['label' => lang('create.tpl_f2_label'), 'text' => lang('create.tpl_f2_text')],
    ['label' => lang('create.tpl_f3_label'), 'text' => lang('create.tpl_f3_text')],
    ['label' => lang('create.tpl_f4_label'), 'text' => lang('create.tpl_f4_text')],
    ['label' => lang('create.tpl_f5_label'), 'text' => lang('create.tpl_f5_text')],
    ['label' => lang('create.tpl_f6_label'), 'text' => lang('create.tpl_f6_text', ['date' => date('Y-m-d'), 'time' => date('H:i')])],
];
?>
<div class="msg-tpl" id="<?= e($pickerId) ?>">
    <div class="msg-tpl-head">
        <strong><?= e(lang('create.tpl_title')) ?></strong>
        <small><?= e(lang('create.tpl_hint')) ?></small>
    </div>
    <div class="msg-tpl-row">
        <?php foreach ($emojis as $emo): ?>
            <button class="tpl-chip tpl-emoji" type="button" data-insert="<?= e($emo) ?>"><?= e($emo) ?></button>
        <?php endforeach; ?>
    </div>
    <div class="msg-tpl-row">
        <button class="tpl-chip" type="button" data-insert="────────\n">────────</button>
        <button class="tpl-chip" type="button" data-insert="• ">•</button>
        <button class="tpl-chip" type="button" data-insert="➡️ "><?= e(lang('create.tpl_arrow')) ?></button>
        <button class="tpl-chip" type="button" data-insert="【<?= e(lang('create.tpl_tag')) ?>】">【<?= e(lang('create.tpl_tag')) ?>】</button>
        <button class="tpl-chip" type="button" data-insert="<?= e(date('Y-m-d')) ?>">📅 <?= e(lang('create.tpl_today')) ?></button>
        <button class="tpl-chip" type="button" data-insert="<?= e(date('H:i')) ?>">⏰ <?= e(lang('create.tpl_now')) ?></button>
    </div>
    <div class="msg-tpl-cards">
        <?php foreach ($frames as $frame): ?>
            <?php $insert = str_replace(["\r\n", "\n"], '\n', $frame['text']); ?>
            <button class="tpl-frame" type="button" data-insert="<?= e($insert) ?>">
                <?= e($frame['label']) ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>
