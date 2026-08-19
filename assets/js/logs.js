(function () {
    const api = window.APP.baseUrl + 'api/logs.php';
    let page = 1, filter = 'all';
    function load() {
        apiPost(api, { action: 'list', search: $('#searchInput').val(), filter: filter, page: page }).done(function (res) {
            const rows = res.data || [];
            if (!rows.length) { $('#logTable').html('<tr><td colspan="6" class="empty-state">' + escapeHtml(t('js.no_logs')) + '</td></tr>'); return; }
            $('#logTable').html(rows.map(function (r) {
                const cls = r.status === 'sent' ? 'badge-sent' : 'badge-failed';
                const label = r.status === 'sent' ? t('status.sent') : t('status.failed');
                return '<tr><td>' + escapeHtml(r.reminder_title) + '</td><td>' + escapeHtml(r.user_name || r.chat_id) + '<br><code>' + escapeHtml(r.chat_id) + '</code></td><td>' + escapeHtml((r.message_text || '').slice(0, 60)) + '</td><td><span class="status-badge ' + cls + '">' + escapeHtml(label) + '</span></td><td>' + escapeHtml(r.sent_time || '') + '</td><td>' + escapeHtml(r.error_message || '—') + '</td></tr>';
            }).join(''));
            renderPager('#pager', res.pager, function (p) { page = p; load(); });
        });
    }
    $('#filterGroup').on('click', '.filter-chip', function () {
        $('.filter-chip').removeClass('active'); $(this).addClass('active'); filter = $(this).data('filter'); page = 1; load();
    });
    let timer; $('#searchInput').on('input', function () { clearTimeout(timer); timer = setTimeout(function () { page = 1; load(); }, 300); });
    load();
})();
