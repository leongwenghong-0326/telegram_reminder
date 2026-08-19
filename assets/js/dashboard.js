(function () {
    const api = window.APP.baseUrl + 'api/reminders.php';
    const noteApi = window.APP.baseUrl + 'api/templates.php';

    function addBox(text) {
        const n = $('#quickMessages .msg-block').length + 1;
        const block = $('<div class="msg-block"><strong>' + t('js.msg', { n: n }) + '</strong><textarea class="form-control mt-2 q-msg"></textarea></div>');
        block.find('textarea').val(text || '');
        $('#quickMessages').append(block);
        block.find('textarea').focus();
        lastBox = block.find('textarea')[0];
    }

    let lastBox = null;
    $('#quickMessages').on('focus', '.q-msg', function () {
        lastBox = this;
    });

    function insertInto(box, text) {
        if (!box) {
            return;
        }
        const start = box.selectionStart ?? box.value.length;
        const end = box.selectionEnd ?? box.value.length;
        const before = box.value.slice(0, start);
        const after = box.value.slice(end);
        box.value = before + text + after;
        const pos = start + text.length;
        box.focus();
        box.setSelectionRange(pos, pos);
        return box;
    }

    function insertText(text) {
        const box = lastBox || $('#quickMessages .q-msg').last()[0];
        if (!box) {
            addBox(text);
            return;
        }
        lastBox = insertInto(box, text);
    }

    function insertNote(text) {
        insertInto(document.getElementById('noteText'), text);
    }

    function bindPicker(toggleSel, panelSel, onInsert) {
        $(toggleSel).on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const panel = $(panelSel);
            const willOpen = !panel.hasClass('open');
            $('.msg-tpl').removeClass('open');
            if (willOpen) {
                panel.addClass('open');
            }
        });
        $(panelSel).on('click', '[data-insert]', function () {
            onInsert(($(this).attr('data-insert') || '').replace(/\\n/g, '\n'));
            if ($(this).hasClass('tpl-frame')) {
                toast(t('js.tpl_inserted'), 'success');
            }
        });
    }

    bindPicker('#tplToggle', '#msgTpl', insertText);
    bindPicker('#noteTplToggle', '#noteTpl', insertNote);

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.msg-tpl, #tplToggle, #noteTplToggle').length) {
            $('.msg-tpl').removeClass('open');
        }
    });

    $('#addQuickMsg').on('click', function (e) {
        e.preventDefault();
        addBox('');
    });
    addBox('');

    const soon = new Date(Date.now() + 3 * 60 * 1000);
    const pad = (v) => String(v).padStart(2, '0');
    $('#scheduled_time').val(soon.getFullYear() + '-' + pad(soon.getMonth() + 1) + '-' + pad(soon.getDate()) + 'T' + pad(soon.getHours()) + ':' + pad(soon.getMinutes()));

    $('#quickForm').on('submit', function (e) {
        e.preventDefault();
        apiPost(api, {
            action: 'save',
            title: $('#title').val(),
            scheduled_time: $('#scheduled_time').val(),
            bot_token: $('#bot_token').val(),
            chat_ids: [$('#chat_id').val()],
            messages: $('.q-msg').map(function () { return this.value; }).get()
        }).done(function (res) {
            toast(res.message, 'success');
            setTimeout(function () { location.reload(); }, 800);
        }).fail(function (xhr) {
            toast((xhr.responseJSON && xhr.responseJSON.message) || t('js.save_fail'), 'error');
        });
    });

    $('#saveNote').on('click', function () {
        const message = $.trim($('#noteText').val() || '');
        if (!message) {
            toast(t('js.need_note'), 'error');
            return;
        }
        apiPost(noteApi, {
            action: 'save',
            message_text: message
        }).done(function (res) {
            toast(res.message, 'success');
            setTimeout(function () { location.reload(); }, 400);
        }).fail(function (xhr) {
            toast((xhr.responseJSON && xhr.responseJSON.message) || t('js.save_fail'), 'error');
        });
    });

    $('#noteList').on('click', '.note-use', function () {
        const text = $(this).closest('.note-item').find('.note-raw').val() || '';
        const box = $('#quickMessages .q-msg').first();
        if (box.length) {
            box.val(text).focus();
        } else {
            addBox(text);
        }
        toast(t('js.filled'), 'success');
    });

    $('#noteList').on('click', '.note-del', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const item = $(this).closest('.note-item');
        apiPost(noteApi, {
            action: 'delete',
            id: $(this).data('id')
        }).done(function (res) {
            item.remove();
            toast(res.message, 'success');
            if (!$('#noteList .note-item').length && !$('#noteEmpty').length) {
                $('#noteList').append('<p class="text-muted small mb-0" id="noteEmpty">' + escapeHtml(t('js.no_notes')) + '</p>');
            }
        }).fail(function (xhr) {
            toast((xhr.responseJSON && xhr.responseJSON.message) || t('js.delete_fail'), 'error');
        });
    });
})();
