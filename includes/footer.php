        </main>
    </div>
</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.APP = {
        baseUrl: <?= json_encode(base_url()) ?>,
        csrfToken: <?= json_encode(csrf_token()) ?>,
        adminId: <?= (int) ($admin['id'] ?? 0) ?>,
        cronUrl: <?= json_encode(base_url('cron/send_reminders.php?key=' . urlencode(CRON_SECRET_KEY))) ?>,
        lang: <?= json_encode(current_lang()) ?>,
        i18n: <?= json_encode($GLOBALS['I18N'] ?? [], JSON_UNESCAPED_UNICODE) ?>
    };
</script>
<script src="<?= e(asset_url('assets/js/app.js')) ?>"></script>
<?php if (!empty($pageScripts)): foreach ($pageScripts as $src): ?>
    <script src="<?= e(asset_url($src)) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
