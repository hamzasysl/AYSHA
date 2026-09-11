import Alpine from 'alpinejs';
import TomSelect from 'tom-select';
import flatpickr from 'flatpickr';
import { Turkish } from 'flatpickr/dist/l10n/tr.js';

window.Alpine = Alpine;

// ---- Para yardımcıları ----
window.formatMoney = (value) =>
    new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value) || 0) + ' ₺';

window.parseMoney = (value) => {
    if (typeof value === 'number') return value;
    const s = String(value ?? '').replace(/[^\d,.-]/g, '');
    if (!s) return 0;
    if (s.includes(',')) return parseFloat(s.replace(/\./g, '').replace(',', '.')) || 0;
    return parseFloat(s) || 0;
};

// ---- UI Select (Tom Select) ----
// Kullanım: <select x-init="uiSelect($el)"> ... </select>
window.uiSelect = (el, opts = {}) => {
    if (!el || el.tomselect) return el?.tomselect;
    const hasPlaceholder = el.querySelector('option[value=""]');
    const ts = new TomSelect(el, {
        allowEmptyOption: true,
        create: el.dataset.create === 'true',
        createOnBlur: el.dataset.create === 'true',
        placeholder: el.dataset.placeholder || (hasPlaceholder ? hasPlaceholder.textContent.trim() : 'Seçiniz'),
        maxOptions: 500,
        dropdownParent: 'body', // kart/tablo overflow'unda kesilmesin
        plugins: el.multiple ? ['remove_button'] : (el.dataset.clear === 'true' ? ['clear_button'] : []),
        controlInput: el.dataset.search === 'false' ? null : undefined,
        render: {
            no_results: () => '<div class="no-results">Sonuç bulunamadı</div>',
        },
        ...opts,
    });
    // onchange="this.form.submit()" gibi native davranışlar korunur
    return ts;
};

// ---- Tarih seçici (flatpickr) ----
// Kullanım: <input x-init="datePicker($el)" value="2026-09-04">  -> görünen 04.09.2026, gönderilen Y-m-d
window.datePicker = (el, opts = {}) => {
    if (!el || el._flatpickr) return el?._flatpickr;
    return flatpickr(el, {
        locale: Turkish,
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd.m.Y',
        allowInput: true,
        disableMobile: true,
        monthSelectorType: 'dropdown',
        ...opts,
    });
};

// ---- Not paneli (AJAX) ----
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const jsonFetch = async (url, method, body) => {
    const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        body: body ? JSON.stringify(body) : undefined,
        credentials: 'same-origin',
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || 'İşlem başarısız');
    return data;
};

window.notesPanel = ({ notes, storeUrl, key }) => ({
    notes: notes || [],
    newText: '',
    editingId: null,
    editText: '',
    busy: false,
    error: '',
    emitCount() { window.dispatchEvent(new CustomEvent('notes-count', { detail: { key, count: this.notes.length } })); },
    async add() {
        if (!this.newText.trim() || this.busy) return;
        this.busy = true; this.error = '';
        try {
            const data = await jsonFetch(storeUrl, 'POST', { content: this.newText.trim() });
            this.notes.unshift(data.note);
            this.newText = '';
            this.emitCount();
        } catch (e) { this.error = e.message; } finally { this.busy = false; }
    },
    startEdit(n) { this.editingId = n.id; this.editText = n.content; },
    async saveEdit(n) {
        if (!this.editText.trim() || this.busy) return;
        this.busy = true; this.error = '';
        try {
            const data = await jsonFetch(`/notlar/${n.id}`, 'PATCH', { content: this.editText.trim() });
            Object.assign(n, data.note);
            this.editingId = null;
        } catch (e) { this.error = e.message; } finally { this.busy = false; }
    },
    async remove(n) {
        if (!confirm('Bu not silinsin mi?')) return;
        this.busy = true; this.error = '';
        try {
            await jsonFetch(`/notlar/${n.id}`, 'DELETE');
            this.notes = this.notes.filter(x => x.id !== n.id);
            this.emitCount();
        } catch (e) { this.error = e.message; } finally { this.busy = false; }
    },
});

// ---- Tema (karanlık mod) ----
window.applyTheme = (t) => { document.documentElement.classList.toggle('dark', t === 'dark'); try { localStorage.setItem('aysha_theme', t); } catch (e) {} };
window.toggleTheme = () => window.applyTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
window.isDark = () => document.documentElement.classList.contains('dark');

Alpine.start();
