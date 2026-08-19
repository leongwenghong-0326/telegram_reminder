(function () {
    const api = window.APP.baseUrl + 'api/reminders.php';
    const userApi = window.APP.baseUrl + 'api/users.php';
    let page = 1, filter = 'all', users = [];
    const modal = new bootstrap.Modal('#reminderModal');
    const badges = {
        pending: ['badge-pending', t('status.pending')],
        sent: ['badge-sent', t('status.sent')],
        failed: ['badge-failed', t('status.failed')],
        partially_sent: ['badge-partial', t('status.partially_sent')]
    };
    function badge(s) {
        const b = badges[s] || ['badge-pending', s];
        return '<span class="status-badge ' + b[0] + '">' + escapeHtml(b[1]) + '</span>';
    }
    function load() {
        apiPost(api, { action: 'list', search: $('#searchInput').val(), filter: filter, page: page }).done(function (res) {
            const rows = res.data || [];
            if (!rows.length) { $('#reminderTable').html('<tr><td colspan="6" class="empty-state">' + escapeHtml(t('js.no_reminders')) + '</td></tr>'); return; }
            $('#reminderTable').html(rows.map(function (r) {
                return '<tr><td>' + escapeHtml(r.title) + '</td><td>' + escapeHtml((r.scheduled_time || '').slice(0, 16)) + '</td><td>' + escapeHtml(r.message_count) + '</td><td><small>' + escapeHtml(r.chat_ids || '') + '</small></td><td>' + badge(r.status) + '</td><td class="text-nowrap">' +
                    (r.status === 'pending' ? '<button class="btn btn-sm btn-outline-secondary me-1 btn-edit" data-id="' + r.id + '">' + escapeHtml(t('js.edit')) + '</button>' : '') +
                    (r.status === 'failed' || r.status === 'partially_sent' ? '<button class="btn btn-sm btn-outline-warning me-1 btn-retry" data-id="' + r.id + '">' + escapeHtml(t('js.retry')) + '</button>' : '') +
                    '<button class="btn btn-sm btn-outline-danger btn-del" data-id="' + r.id + '">' + escapeHtml(t('js.delete')) + '</button></td></tr>';
            }).join(''));
            renderPager('#pager', res.pager, function (p) { page = p; load(); });
        });
    }
    function addMsg(text) {
        const block = $('<div class="msg-block"><div class="d-flex justify-content-between mb-2"><strong class="msg-label">' + escapeHtml(t('js.msg_label')) + '</strong><button class="btn btn-sm btn-outline-danger btn-remove" type="button">' + escapeHtml(t('js.delete')) + '</button></div><textarea class="form-control msg-text" placeholder="' + escapeHtml(t('js.input_here')) + '"></textarea></div>');
        block.find('textarea').val(text || '');
        $('#messageList').append(block);
        $('#messageList .msg-label').each(function (i) { $(this).text(t('js.msg', { n: i + 1 })); });
        block[0].scrollIntoView({ block: 'nearest' });
        block.find('textarea').focus();
    }
    function renderUsers(selected) {
        selected = (selected || []).map(String);
        const q = ($('#userSearch').val() || '').toLowerCase();
        const filtered = users.filter(function (u) { return !q || (u.name + ' ' + u.chat_id).toLowerCase().indexOf(q) !== -1; });
        if (!filtered.length) {
            $('#userPicker').html('<div class="text-muted small p-2">' + escapeHtml(t('js.no_match')) + '</div>');
            return;
        }
        $('#userPicker').html(filtered.map(function (u) {
            return '<label class="d-flex gap-2 py-1"><input type="checkbox" value="' + u.id + '"' + (selected.indexOf(String(u.id)) !== -1 ? ' checked' : '') + '> ' + escapeHtml(u.name) + ' <code>' + escapeHtml(u.chat_id) + '</code></label>';
        }).join(''));
    }
    $('#addMessage').on('click', function (e) { e.preventDefault(); addMsg(''); });
    $('#messageList').on('click', '.btn-remove', function () {
        if ($('#messageList .msg-block').length <= 1) { toast(t('js.keep_one'), 'error'); return; }
        $(this).closest('.msg-block').remove();
    });
    $('#btnAddChat').on('click', function (e) {
        e.preventDefault();
        const chatId = $('#userSearch').val().trim();
        if (!/^-?\d{5,20}$/.test(chatId)) { toast(t('js.need_chat_num'), 'error'); return; }
        const selected = $('#userPicker input:checked').map(function () { return String(this.value); }).get();
        apiPost(userApi, { action: 'save', name: 'Telegram ' + chatId, chat_id: chatId }).done(function (res) {
            if (res.id) selected.push(String(res.id));
            $('#userSearch').val('');
            apiPost(userApi, { action: 'options' }).done(function (r) { users = r.data || []; renderUsers(selected); });
        }).fail(function (xhr) { toast((xhr.responseJSON && xhr.responseJSON.message) || t('js.add_fail'), 'error'); });
    });
    $('#userSearch').on('input', function () {
        renderUsers($('#userPicker input:checked').map(function () { return this.value; }).get());
    });
    $('#reminderForm').on('submit', function (e) {
        e.preventDefault();
        apiPost(api, {
            action: 'save',
            id: $('#reminderId').val(),
            title: $('#title').val(),
            scheduled_time: $('#scheduled_time').val(),
            user_ids: $('#userPicker input:checked').map(function () { return this.value; }).get(),
            messages: $('.msg-text').map(function () { return this.value; }).get()
        }).done(function (res) { toast(res.message, 'success'); modal.hide(); load(); })
          .fail(function (xhr) { toast((xhr.responseJSON && xhr.responseJSON.message) || t('js.save_fail'), 'error'); });
    });
    $('#reminderTable').on('click', '.btn-edit', function () {
        apiPost(api, { action: 'get', id: $(this).data('id') }).done(function (res) {
            const d = res.data;
            $('#reminderId').val(d.reminder.id);
            $('#title').val(d.reminder.title);
            $('#scheduled_time').val((d.reminder.scheduled_time || '').replace(' ', 'T').slice(0, 16));
            $('#messageList').empty();
            (d.messages || []).forEach(function (m) { addMsg(m.message_text); });
            if (!d.messages.length) addMsg('');
            apiPost(userApi, { action: 'options' }).done(function (r) { users = r.data || []; renderUsers(d.user_ids || []); modal.show(); });
        });
    }).on('click', '.btn-del', function () {
        if (!confirm(t('js.confirm_delete'))) return;
        apiPost(api, { action: 'delete', id: $(this).data('id') }).done(function (res) { toast(res.message, 'success'); load(); });
    }).on('click', '.btn-retry', function () {
        apiPost(api, { action: 'retry', id: $(this).data('id') }).done(function (res) { toast(res.message, 'success'); load(); });
    });
    $('#filterGroup').on('click', '.filter-chip', function () {
        $('.filter-chip').removeClass('active'); $(this).addClass('active'); filter = $(this).data('filter'); page = 1; load();
    });
    let timer; $('#searchInput').on('input', function () { clearTimeout(timer); timer = setTimeout(function () { page = 1; load(); }, 300); });
    load();
})();
