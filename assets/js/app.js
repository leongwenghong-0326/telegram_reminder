(function () {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    document.getElementById('sidebarToggle')?.addEventListener('click', function () {
        sidebar.classList.toggle('open');
        backdrop.classList.toggle('show');
    });
    backdrop?.addEventListener('click', function () {
        sidebar.classList.remove('open');
        backdrop.classList.remove('show');
    });
    const clock = document.getElementById('liveClock');
    if (clock) {
        const tick = () => {
            const n = new Date(), p = (v) => String(v).padStart(2, '0');
            clock.textContent = n.getFullYear() + '-' + p(n.getMonth() + 1) + '-' + p(n.getDate()) + ' ' + p(n.getHours()) + ':' + p(n.getMinutes()) + ':' + p(n.getSeconds());
        };
        tick();
        setInterval(tick, 1000);
    }
    $(document).ajaxError(function (_e, xhr) {
        if (xhr.status === 401) window.location.href = window.APP.baseUrl + 'admin/login.php';
    });
    window.apiPost = function (url, data) {
        return $.ajax({ url: url, method: 'POST', dataType: 'json', data: Object.assign({ csrf_token: window.APP.csrfToken }, data) });
    };
    window.t = function (key, vars) {
        let s = (window.APP && window.APP.i18n && window.APP.i18n[key]) || key;
        if (vars) {
            Object.keys(vars).forEach(function (k) {
                s = s.split(':' + k).join(String(vars[k]));
            });
        }
        return s;
    };
    window.toast = function (message, type) {
        const cls = type === 'success' ? 'alert-success' : 'alert-danger';
        $('.page-content').prepend('<div class="alert ' + cls + ' alert-dismissible fade show">' + $('<div>').text(message).html() + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
        setTimeout(function () { $('.page-content > .alert').first().alert('close'); }, 4000);
    };
    window.escapeHtml = function (v) { return $('<div>').text(v == null ? '' : String(v)).html(); };
    window.renderPager = function (el, pager, onClick) {
        if (!pager || pager.pages <= 1) { $(el).html(''); return; }
        let html = '';
        for (let i = 1; i <= pager.pages; i++) {
            html += '<li class="page-item ' + (i === pager.page ? 'active' : '') + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
        }
        $(el).html(html).off('click').on('click', 'a', function (e) { e.preventDefault(); onClick(parseInt(this.dataset.page, 10)); });
    };
    if (window.APP.cronUrl) {
        setInterval(function () { $.get(window.APP.cronUrl); }, 20000);
    }
})();
