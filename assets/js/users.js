(function () {
    const api = window.APP.baseUrl + 'api/users.php';
    let page = 1;
    const modal = new bootstrap.Modal('#userModal');
    function load() {
        apiPost(api, { action: 'list', search: $('#searchInput').val(), page: page }).done(function (res) {
            const rows = res.data || [];
            if (!rows.length) { $('#userTable').html('<tr><td colspan="4" class="empty-state">' + escapeHtml(t('js.no_users')) + '</td></tr>'); return; }
            $('#userTable').html(rows.map(function (u) {
                return '<tr><td>' + escapeHtml(u.name) + '</td><td><code>' + escapeHtml(u.chat_id) + '</code></td><td>' + escapeHtml(u.reminder_count) + '</td><td>' +
                    '<button class="btn btn-sm btn-outline-success me-1 btn-test" data-id="' + u.id + '">' + escapeHtml(t('js.test')) + '</button>' +
                    '<button class="btn btn-sm btn-outline-danger btn-del" data-id="' + u.id + '">' + escapeHtml(t('js.delete')) + '</button></td></tr>';
            }).join(''));
            renderPager('#pager', res.pager, function (p) { page = p; load(); });
        });
    }
    $('#btnCreate').on('click', function () { $('#userForm')[0].reset(); $('#userId').val(''); modal.show(); });
    $('#userForm').on('submit', function (e) {
        e.preventDefault();
        apiPost(api, { action: 'save', id: $('#userId').val(), name: $('#name').val(), chat_id: $('#chat_id').val() }).done(function (res) {
            toast(res.message, 'success'); modal.hide(); load();
        }).fail(function (xhr) { toast((xhr.responseJSON && xhr.responseJSON.message) || t('js.save_fail'), 'error'); });
    });
    $('#userTable').on('click', '.btn-del', function () {
        if (!confirm(t('js.confirm_delete'))) return;
        apiPost(api, { action: 'delete', id: $(this).data('id') }).done(function (res) { toast(res.message, 'success'); load(); });
    }).on('click', '.btn-test', function () {
        apiPost(api, { action: 'test_send', id: $(this).data('id') }).done(function (res) { toast(res.message, 'success'); })
            .fail(function (xhr) { toast((xhr.responseJSON && xhr.responseJSON.message) || t('js.send_fail'), 'error'); });
    });
    let timer; $('#searchInput').on('input', function () { clearTimeout(timer); timer = setTimeout(function () { page = 1; load(); }, 300); });
    load();
})();
