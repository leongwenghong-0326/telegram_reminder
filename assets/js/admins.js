(function () {
    const api = window.APP.baseUrl + 'api/admins.php';
    const modal = new bootstrap.Modal('#adminModal');
    function load() {
        apiPost(api, { action: 'list' }).done(function (res) {
            $('#adminTable').html((res.data || []).map(function (a) {
                const self = Number(a.id) === Number(window.APP.adminId);
                return '<tr><td>' + escapeHtml(a.username) + (self ? ' <small>' + escapeHtml(t('js.me')) + '</small>' : '') + '</td><td>' + escapeHtml(a.email) + '</td><td>' + escapeHtml(a.last_login || '—') + '</td><td>' + (self ? '—' : '<button class="btn btn-sm btn-outline-danger btn-del" data-id="' + a.id + '">' + escapeHtml(t('js.delete')) + '</button>') + '</td></tr>';
            }).join(''));
        });
    }
    $('#btnCreate').on('click', function () { $('#adminForm')[0].reset(); modal.show(); });
    $('#adminForm').on('submit', function (e) {
        e.preventDefault();
        apiPost(api, { action: 'create', username: this.username.value, email: this.email.value, password: this.password.value }).done(function (res) {
            toast(res.message, 'success'); modal.hide(); load();
        }).fail(function (xhr) { toast((xhr.responseJSON && xhr.responseJSON.message) || t('js.fail'), 'error'); });
    });
    $('#adminTable').on('click', '.btn-del', function () {
        if (!confirm(t('js.confirm_delete'))) return;
        apiPost(api, { action: 'delete', id: $(this).data('id') }).done(function (res) { toast(res.message, 'success'); load(); });
    });
    $('#passwordForm').on('submit', function (e) {
        e.preventDefault();
        apiPost(api, { action: 'change_password', old_password: this.old_password.value, new_password: this.new_password.value }).done(function (res) {
            toast(res.message, 'success'); e.target.reset();
        }).fail(function (xhr) { toast((xhr.responseJSON && xhr.responseJSON.message) || t('js.fail'), 'error'); });
    });
    load();
})();
