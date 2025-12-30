document.addEventListener('DOMContentLoaded', () => {
    initTimers();
    initSessionButtons();
    initPosSearch();
});

function initTimers() {
    const timerElements = document.querySelectorAll('[data-start-time]');
    timerElements.forEach(el => {
        updateTimer(el);
        setInterval(() => updateTimer(el), 1000);
    });
    const maintEls = document.querySelectorAll('[data-maintenance-end]');
    maintEls.forEach(el => {
        updateMaintenanceTimer(el);
        setInterval(() => updateMaintenanceTimer(el), 1000);
    });
}

function updateTimer(el) {
    const start = el.getAttribute('data-start-time');
    if (!start) return;
    const startTime = new Date(start.replace(' ', 'T')).getTime();
    const now = Date.now();
    const diffMs = now - startTime;
    const minutes = Math.floor(diffMs / 60000);
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    const secs = Math.floor(diffMs / 1000) % 60;
    el.textContent = `${pad(hours)}:${pad(mins)}:${pad(secs)}`;
    checkSessionAlert(el, minutes);
}

function pad(n) {
    return n.toString().padStart(2, '0');
}

function initSessionButtons() {
    document.querySelectorAll('.btn-start-session').forEach(btn => {
        btn.addEventListener('click', () => openStartModal(btn.getAttribute('data-table')));
    });
    document.querySelectorAll('.btn-stop-session').forEach(btn => {
        btn.addEventListener('click', () => handleSessionAction(btn, 'stop'));
    });
}

function initPosSearch() {
    const searchInput = document.getElementById('productSearch');
    if (!searchInput) return;
    const filterBySearch = () => {
        const term = searchInput.value.trim().toLowerCase();
        document.querySelectorAll('#productGrid > div[data-name]').forEach(card => {
            const name = card.getAttribute('data-name') || '';
            card.style.display = name.includes(term) ? '' : 'none';
        });
    };
    searchInput.addEventListener('input', filterBySearch);
}

function openStartModal(tableId) {
    const modal = document.getElementById('startModal');
    if (!modal) {
        handleSessionAction({ getAttribute: () => tableId }, 'start'); // fallback
        return;
    }
    modal.querySelector('input[name="table_id"]').value = tableId;
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

function syncMemberSelect(val) {
    const select = document.getElementById('memberSelect');
    if (!select) return;
    const options = Array.from(select.options);
    const found = options.find(opt => opt.text.toLowerCase().includes(val.toLowerCase()));
    if (found) {
        select.value = found.value;
    }
}

// Auto-pick member when typing/enter in datalist input
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('memberSearchInput');
    const select = document.getElementById('memberSelect');
    if (!input || !select) return;
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = input.value.trim().toLowerCase();
            const opts = Array.from(select.options);
            const exact = opts.find(o => o.text.toLowerCase().includes(val));
            if (exact) {
                select.value = exact.value;
            }
        }
    });
    input.addEventListener('blur', () => {
        const val = input.value.trim().toLowerCase();
        const opts = Array.from(select.options);
        const exact = opts.find(o => o.text.toLowerCase().includes(val));
        if (exact) select.value = exact.value;
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const startForm = document.getElementById('startForm');
    if (startForm) {
        startForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const btn = startForm.querySelector('button[type="submit"]');
            btn.disabled = true;
            const formData = new FormData(startForm);
            const tabUmum = document.getElementById('tab-umum');
            const isUmumActive = tabUmum && tabUmum.classList.contains('active');
            const customerName = (formData.get('customer_name') || '').trim();
            const customerPhone = (formData.get('customer_phone') || '').trim();
            let mode = isUmumActive ? 'umum' : 'member';
            if (customerName || customerPhone) {
                mode = 'umum';
            }
            formData.set('mode', mode);
            if (mode === 'umum') {
                formData.set('member_id', '');
            } else {
                formData.set('customer_name', '');
                formData.set('customer_phone', '');
            }
            fetch('/billiard_pos/tables/start_session.php', { method: 'POST', body: formData })
                .then(async r => {
                    const text = await r.text();
                    try { return JSON.parse(text); } catch (e) { throw new Error(text || 'Invalid response'); }
                })
                .then(data => {
                    if (data.status === 'ok') {
                        location.reload();
                    } else {
                        alert(data.message || 'Terjadi kesalahan.');
                    }
                })
                .catch(err => alert('Gagal memulai: ' + err.message))
                .finally(() => { btn.disabled = false; });
        });
    }

    initMaintenanceButtons();
    initProductFilter();
});

function handleSessionAction(btn, action) {
    const tableId = btn.getAttribute('data-table');
    const url = action === 'start' ? '/billiard_pos/tables/start_session.php' : '/billiard_pos/tables/stop_session.php';
    const formData = new FormData();
    formData.append('table_id', tableId);

    btn.disabled = true;
    fetch(url, { method: 'POST', body: formData })
        .then(async r => {
            const text = await r.text();
            try { return JSON.parse(text); } catch (e) { throw new Error(text || 'Invalid response'); }
        })
        .then(data => {
            if (data.status === 'ok') {
                if (action === 'stop') {
                    window.location.href = `/billiard_pos/pos/checkout.php?table_id=${tableId}`;
                } else {
                    location.reload();
                }
            } else {
                alert(data.message || 'Terjadi kesalahan.');
            }
        })
        .catch(err => alert('Gagal memproses: ' + err.message))
        .finally(() => { btn.disabled = false; });
}

function initMaintenanceButtons() {
    document.querySelectorAll('.btn-maintenance').forEach(btn => {
        btn.addEventListener('click', () => {
            const tableId = btn.getAttribute('data-table');
            const action = btn.getAttribute('data-maint-action') || 'on';
            const pass = prompt(action === 'on' ? 'Password maintenance:' : 'Password matikan maintenance:');
            if (!pass) return;
            let duration = null;
            if (window.APP_ROLE === 'admin' && action === 'on') {
                const inputDur = prompt('Durasi maintenance (menit)?', '60');
                if (inputDur) duration = parseInt(inputDur, 10) || null;
            }
            handleMaintenance(tableId, action, pass, btn, duration);
        });
    });
}

function handleMaintenance(tableId, action, pass, btn, duration) {
    const formData = new FormData();
    formData.append('table_id', tableId);
    formData.append('action', action);
    formData.append('password', pass);
    if (duration) formData.append('duration', duration);
    if (btn) btn.disabled = true;
    fetch('/billiard_pos/tables/maintenance.php', { method: 'POST', body: formData })
        .then(async r => {
            const text = await r.text();
            try { return JSON.parse(text); } catch (e) { throw new Error(text || 'Invalid response'); }
        })
        .then(data => {
            if (data.status === 'ok') {
                alert(data.message);
                setTimeout(() => window.location.reload(), 800);
            } else {
                alert(data.message || 'Gagal maintenance');
            }
        })
        .catch(err => alert('Gagal maintenance: ' + err.message))
        .finally(() => { if (btn) btn.disabled = false; });
}

function updateMaintenanceTimer(el) {
    const end = el.getAttribute('data-maintenance-end');
    if (!end) return;
    const endTime = new Date(end.replace(' ', 'T')).getTime();
    const diff = Math.max(0, endTime - Date.now());
    const minutes = Math.floor(diff / 60000);
    const seconds = Math.floor((diff % 60000) / 1000);
    el.textContent = `${pad(minutes)}:${pad(seconds)}`;
    if (diff <= 0) {
        el.textContent = '00:00';
        // auto refresh to reflect status change
        setTimeout(() => window.location.reload(), 1500);
    }
}

function initProductFilter() {
    const btns = document.querySelectorAll('.filter-btn');
    const cards = document.querySelectorAll('#productGrid [data-cat]');
    if (!btns.length) return;
    btns.forEach(b => b.addEventListener('click', () => {
        btns.forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        const cat = b.getAttribute('data-cat');
        cards.forEach(c => {
            const show = cat === 'all' || c.getAttribute('data-cat') === cat;
            c.style.display = show ? '' : 'none';
        });
    }));
}

function checkSessionAlert(el, minutes) {
    const sessionId = el.getAttribute('data-session-id');
    if (!sessionId) return;
    const pkgDuration = parseInt(el.getAttribute('data-pkg-duration') || '0', 10);
    const defaultThreshold = pkgDuration ? Math.max(0, pkgDuration - 5) : 55; // menit
    const nextKey = `session_alert_next_${sessionId}`;
    let nextThreshold = parseInt(localStorage.getItem(nextKey) || defaultThreshold, 10);
    if (minutes >= nextThreshold) {
        el.classList.add('timer-alert'); // blinking indicator
        nextThreshold += 60; // alert lagi setiap jam berikutnya (minus 5 menit)
        localStorage.setItem(nextKey, nextThreshold);
    }
}
