(function () {
    const api = window.APP.baseUrl + 'api/settings.php';
    function load() {
        apiPost(api, { action: 'get' }).done(function (res) {
            const d = res.data || {};
            if (d.has_token) {
                $('#bot_token').attr('placeholder', t('js.token_saved', { token: d.masked_token }));
                $('#tokenHint').text(t('js.token_ok'));
            }
            if (d.default_chat) $('#chat_id').val(d.default_chat);
        });
    }
    $('#settingsForm').on('submit', function (e) {
        e.preventDefault();
        apiPost(api, { action: 'save', bot_token: $('#bot_token').val(), chat_id: $('#chat_id').val() }).done(function (res) {
            toast(res.message, 'success');
            $('#bot_token').val('');
            load();
        }).fail(function (xhr) {
            toast((xhr.responseJSON && xhr.responseJSON.message) || t('js.save_fail'), 'error');
        });
    });
    $('#btnTest').on('click', function () {
        apiPost(api, { action: 'test', chat_id: $('#chat_id').val() }).done(function (res) {
            toast(res.message, 'success');
        }).fail(function (xhr) {
            toast((xhr.responseJSON && xhr.responseJSON.message) || t('js.send_fail'), 'error');
        });
    });
    load();
})();
