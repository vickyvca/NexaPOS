</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Format Rupiah untuk input dengan class rupiah-input
// Logika parsing: titik 3 digit dianggap ribuan, selain itu dianggap desimal.
window.parseMoneyIndo = function(val){
    let s = (val ?? '').toString().trim().replace(/\s+/g,'').replace(/,/g,'.');
    if (!s) return 0;
    const parts = s.split('.');
    if (parts.length === 1) {
        return parseInt(parts[0] || '0', 10) || 0;
    }
    if (parts.length === 2) {
        if (parts[1].length === 3) { // ribuan
            return parseInt(parts[0] + parts[1], 10) || 0;
        }
        const n = parseFloat(parts[0] + '.' + parts[1]);
        return isNaN(n) ? 0 : Math.round(n);
    }
    // lebih dari 1 titik: anggap semua titik ribuan kecuali terakhir (desimal jika !=3)
    const dec = parts.pop();
    if (dec.length === 3) {
        return parseInt(parts.join('') + dec, 10) || 0;
    }
    const n = parseFloat(parts.join('') + '.' + dec);
    return isNaN(n) ? 0 : Math.round(n);
};
window.formatMoneyIndo = function(n){
    const num = typeof n === 'number' ? n : parseMoneyIndo(n);
    if (!num) return '';
    return num.toLocaleString('id-ID');
};

document.addEventListener('DOMContentLoaded', () => {
    const bindMoney = (inp) => {
        if (inp.dataset.bound) return;
        inp.dataset.bound = '1';
        if (inp.value) inp.value = formatMoneyIndo(inp.value);
        inp.addEventListener('input', () => { inp.value = formatMoneyIndo(inp.value); });
        inp.addEventListener('blur', () => { inp.value = formatMoneyIndo(inp.value); });
    };
    document.querySelectorAll('.rupiah-input').forEach(bindMoney);
    document.addEventListener('focusin', (e)=>{
        if (e.target.classList && e.target.classList.contains('rupiah-input')) bindMoney(e.target);
    });
    // sebelum submit kembalikan ke angka polos
    document.querySelectorAll('form').forEach(f => {
        f.addEventListener('submit', () => {
            f.querySelectorAll('.rupiah-input').forEach(el => {
                el.value = parseMoneyIndo(el.value);
            });
        });
    });
});
</script>
</body>
</html>
