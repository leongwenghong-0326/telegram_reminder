(function () {
    const pass = document.getElementById('password');
    document.getElementById('togglePass')?.addEventListener('click', function () {
        const show = pass.type === 'password';
        pass.type = show ? 'text' : 'password';
        this.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const visual = document.getElementById('authVisual');
    const card = document.getElementById('authCard');
    const canvas = document.getElementById('authCanvas');
    const form = document.getElementById('loginForm');

    if (form) {
        form.addEventListener('submit', function () {
            form.querySelector('.auth-login-btn')?.classList.add('sending');
        });
    }

    if (!reduced && card) {
        const wrap = card.parentElement;
        wrap.addEventListener('mousemove', function (e) {
            const r = card.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width - 0.5;
            const y = (e.clientY - r.top) / r.height - 0.5;
            card.style.transform = 'rotateX(' + (-y * 7) + 'deg) rotateY(' + (x * 9) + 'deg)';
        });
        wrap.addEventListener('mouseleave', function () {
            card.style.transform = '';
        });
    }

    if (!canvas || reduced) {
        return;
    }

    const ctx = canvas.getContext('2d');
    const dots = [];
    let w = 0, h = 0, mx = 0.5, my = 0.5;

    function resize() {
        w = canvas.width = visual.clientWidth;
        h = canvas.height = visual.clientHeight;
        if (!dots.length) {
            for (let i = 0; i < 42; i++) {
                dots.push({
                    x: Math.random(),
                    y: Math.random(),
                    r: 1 + Math.random() * 1.8,
                    s: 0.12 + Math.random() * 0.28,
                    p: Math.random() * Math.PI * 2
                });
            }
        }
    }
    resize();
    window.addEventListener('resize', resize);
    visual.addEventListener('mousemove', function (e) {
        const r = visual.getBoundingClientRect();
        mx = (e.clientX - r.left) / r.width;
        my = (e.clientY - r.top) / r.height;
    });

    function tick(t) {
        ctx.clearRect(0, 0, w, h);
        const nodes = dots.map(function (d) {
            const x = (d.x + Math.sin(t * 0.00018 + d.p) * 0.04 + (mx - 0.5) * 0.04) * w;
            const y = (d.y + Math.cos(t * 0.00016 + d.p) * 0.03 + (my - 0.5) * 0.04) * h;
            return { x: x, y: y, r: d.r, s: d.s };
        });
        ctx.lineWidth = 1;
        for (let i = 0; i < nodes.length; i++) {
            for (let j = i + 1; j < nodes.length; j++) {
                const dx = nodes[i].x - nodes[j].x;
                const dy = nodes[i].y - nodes[j].y;
                const dist = Math.hypot(dx, dy);
                if (dist < 120) {
                    ctx.strokeStyle = 'rgba(120,210,245,' + (0.16 * (1 - dist / 120)) + ')';
                    ctx.beginPath();
                    ctx.moveTo(nodes[i].x, nodes[i].y);
                    ctx.lineTo(nodes[j].x, nodes[j].y);
                    ctx.stroke();
                }
            }
        }
        nodes.forEach(function (n) {
            ctx.fillStyle = 'rgba(180,230,255,' + (0.35 + n.s * 0.4) + ')';
            ctx.beginPath();
            ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
            ctx.fill();
        });
        requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
})();
