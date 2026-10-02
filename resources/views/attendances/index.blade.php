<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Presensi | Sistem Presensi</title>
    <style>
        * { box-sizing: border-box; } body { margin:0; background:#f8fafc; color:#202333; font-family:Arial,sans-serif; } a { color:#6740d8; text-decoration:none; }
        .container { width:min(100% - 40px,1200px); margin:32px auto; } .card { padding:28px; background:white; border:1px solid #e5e7eb; border-radius:14px; } h1 { font-size:24px; margin:0 0 8px; } .subtitle { color:#747887; font-size:14px; margin:0 0 26px; }
        input,select,button { font:inherit; border:1px solid #dddfe7; border-radius:8px; padding:11px 13px; background:white; color:inherit; } button { cursor:pointer; } button:hover { background:#f4f0ff; } button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible { outline:3px solid #bba5ff; outline-offset:2px; }
        .toolbar { display:flex; gap:12px; align-items:center; margin-bottom:18px; flex-wrap:wrap; } .search { flex:1; min-width:220px; position:relative; } .search input { width:100%; padding-left:40px; } .search svg { position:absolute; left:13px; top:13px; color:#747887; } .primary { background:#6938db; color:white; border-color:#6938db; } .primary:hover { background:#5829c7; } .filter-button { display:flex; gap:8px; align-items:center; } .reset { font-size:14px; } .filter-summary { font-size:13px; color:#6740d8; margin:0 0 16px; }
        .table-wrap { overflow-x:auto; border:1px solid #e2e4ea; border-radius:10px; } table { width:100%; border-collapse:collapse; text-align:left; } th,td { padding:17px 18px; white-space:nowrap; border-bottom:1px solid #e8e9ee; font-size:14px; } th { background:#f8fafc; font-weight:600; } td { color:#606575; } tbody tr:last-child td { border-bottom:0; } .badge { display:inline-block; border-radius:7px; padding:8px 12px; } .badge-hadir { background:#e6f8f0; color:#14795b; } .badge-terlambat { background:#ffe8e9; color:#b82d3c; } .badge-izin { background:#eee8ff; color:#6840b7; } .badge-sakit { background:#fff2d9; color:#986819; }
        .empty-state { padding:40px; text-align:center; color:#747887; } .errors { color:#b91c1c; background:#fff1f2; padding:12px 18px; border-radius:8px; margin-bottom:18px; }
        dialog { width:min(860px,calc(100% - 32px)); border:0; border-radius:16px; padding:0; box-shadow:0 24px 80px #17203930; max-height:90vh; overflow:auto; } dialog::backdrop { background:#18203666; } .modal-head { display:flex; justify-content:space-between; align-items:center; padding:20px 24px; border-bottom:1px solid #e8e9ee; } .modal-head h2 { margin:0; font-size:18px; } .modal-grid { display:grid; grid-template-columns:1.1fr 1fr; } .calendar-panel { padding:24px; border-right:1px solid #e8e9ee; } .calendar-nav { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:20px; } .calendar-nav button { padding:7px 12px; } .calendar-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; } .weekday { text-align:center; padding:8px 0; color:#8a8e9c; font-size:12px; } .day { padding:10px 0; border:0; font-size:14px; } .day.selected { background:#6938db; color:white; } .day.in-range { background:#eee8ff; color:#6740d8; } .calendar-hint { font-size:13px; line-height:1.6; color:#747887; margin:20px 0 0; } .date-form { padding:24px; display:grid; gap:16px; align-content:start; } label { display:grid; gap:7px; font-size:13px; } label input,label select { width:100%; } .mode-fields { display:grid; gap:14px; } [hidden] { display:none !important; } .modal-foot { display:flex; justify-content:space-between; align-items:center; padding:18px 24px; border-top:1px solid #e8e9ee; } .modal-actions { display:flex; gap:10px; }
        @media(max-width:640px) { .container { width:calc(100% - 24px); margin:20px auto; } .card { padding:18px; } .search { flex-basis:100%; } .toolbar select { flex:1; } .modal-grid { grid-template-columns:1fr; } .calendar-panel { border-right:0; border-bottom:1px solid #e8e9ee; } th,td { padding:14px; } }
    </style>
</head>
<body>
    <main class="container">
        <p><a href="{{ route('dashboard') }}">Kembali ke dashboard</a></p>
        <section class="card">
            <h1>Riwayat Presensi Saya</h1>

            <p class="subtitle">Riwayat presensi pribadi Anda.</p>
            <form id="attendance-filters" method="GET" action="{{ route('attendances.index') }}">
                <div class="toolbar">
                    <label class="search" aria-label="Search nama">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/></svg>
                        <input aria-label="Search nama" type="search" name="name" maxlength="255" value="{{ $filters['name'] }}" placeholder="Cari nama Anda…">
                    </label>
                    <button type="button" class="filter-button" id="open-date-filter"><span aria-hidden="true">☷</span> Filter tanggal</button>
                    <select name="status" aria-label="Status">
                        <option value="">Semua status</option>
                        @foreach (['hadir', 'terlambat', 'izin', 'sakit'] as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                        @if ($filters['status'] !== '' && ! in_array($filters['status'], ['hadir', 'terlambat', 'izin', 'sakit']))
                            <option value="{{ $filters['status'] }}" selected>{{ $filters['status'] }} (tidak valid)</option>
                        @endif
                    </select>
                    <button type="submit" class="primary">Cari</button>
                    <a class="reset" href="{{ route('attendances.index') }}">Reset filter</a>
                </div>
                @if (collect($filters)->except(['name', 'status'])->contains(fn ($value) => $value !== ''))
                    <p class="filter-summary">Tanggal aktif:
                        @foreach (['exact_date' => 'Tanggal', 'start_date' => 'Dari', 'end_date' => 'Sampai', 'month' => 'Bulan', 'year' => 'Tahun'] as $key => $label)
                            @if ($filters[$key] !== '') {{ $label }} {{ $filters[$key] }} · @endif
                        @endforeach
                    </p>
                @endif
                <dialog id="date-filter-modal" aria-labelledby="date-filter-title">
                    <div class="modal-head"><h2 id="date-filter-title">Filter tanggal presensi</h2><button type="button" id="close-date-filter" aria-label="Tutup filter">✕</button></div>
                    <div class="modal-grid">
                        <div class="calendar-panel">
                            <div class="calendar-nav"><button type="button" id="previous-month" aria-label="Bulan sebelumnya">‹</button><strong id="calendar-title" aria-live="polite"></strong><button type="button" id="next-month" aria-label="Bulan berikutnya">›</button></div>
                            <div class="calendar-grid" id="calendar"></div>
                            <p class="calendar-hint">Klik satu tanggal untuk tanggal tepat. Klik tanggal kedua untuk rentang. Klik lagi untuk memulai pilihan baru.</p>
                        </div>
                        <div class="date-form">
                            <label>Jenis filter<select id="date-mode"><option value="exact">Tanggal tepat</option><option value="range">Rentang tanggal</option><option value="period">Bulan / tahun</option></select></label>
                            <div class="mode-fields" data-mode="exact"><label>Tanggal tepat (YYYY-MM-DD)<input name="exact_date" value="{{ $filters['exact_date'] }}" placeholder="2026-09-15"></label></div>
                            <div class="mode-fields" data-mode="range"><label>Tanggal awal (YYYY-MM-DD)<input name="start_date" value="{{ $filters['start_date'] }}" placeholder="2026-09-01"></label><label>Tanggal akhir (YYYY-MM-DD)<input name="end_date" value="{{ $filters['end_date'] }}" placeholder="2026-09-30"></label><small class="calendar-hint">Termasuk kedua batas. Boleh mengisi satu batas saja.</small></div>
                            <div class="mode-fields" data-mode="period"><label>Bulan (1–12)<input name="month" inputmode="numeric" value="{{ $filters['month'] }}" placeholder="9"></label><label>Tahun (1–9999)<input name="year" inputmode="numeric" value="{{ $filters['year'] }}" placeholder="2026"></label><small class="calendar-hint">Bulan dan tahun boleh digunakan sendiri-sendiri.</small></div>
                        </div>
                    </div>
                    <div class="modal-foot"><button type="button" id="clear-date-filter">Hapus tanggal</button><div class="modal-actions"><button type="button" id="cancel-date-filter">Batal</button><button type="submit" class="primary">Terapkan filter</button></div></div>
                </dialog>
            </form>

            @if ($filterErrors->any())
                <div class="errors" role="alert">
                    <p>Filter belum diterapkan. Perbaiki input berikut:</p>
                    <ul>
                        @foreach ($filterErrors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @elseif ($attendances->isEmpty())
                <p class="empty-state">{{ $hasFilters ? 'Tidak ada presensi yang cocok dengan filter.' : 'Belum ada data presensi.' }}</p>
            @else
                <div class="table-wrap"><table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $attendance)
                            <tr>
                                <td>{{ $attendance->user->name }}</td>
                                <td>{{ $attendance->attendance_date }}</td>
                                <td>{{ $attendance->check_in_time ?? '—' }}</td>
                                <td>{{ $attendance->check_out_time ?? '—' }}</td>
                                <td><span class="badge badge-{{ $attendance->status }}">{{ ucfirst($attendance->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            @endif
        </section>
    </main>
<script>
(() => {
    const form = document.getElementById('attendance-filters');
    const modal = document.getElementById('date-filter-modal');
    const mode = document.getElementById('date-mode');
    const keys = ['exact_date', 'start_date', 'end_date', 'month', 'year'];
    const inputs = Object.fromEntries(keys.map(key => [key, form.elements[key]]));
    const value = key => inputs[key].value.trim();
    const read = () => Object.fromEntries(keys.map(key => [key, inputs[key].value]));
    const infer = () => value('exact_date') ? 'exact' : (value('start_date') || value('end_date')) ? 'range' : (value('month') || value('year')) ? 'period' : 'exact';
    const parse = text => {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(text)) return null;
        const date = new Date(text + 'T12:00:00');
        return !Number.isNaN(date.getTime()) && format(date) === text ? date : null;
    };
    const format = date => `${String(date.getFullYear()).padStart(4, '0')}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    let visible = new Date();
    let snapshot;
    let savedMode;
    const showMode = () => document.querySelectorAll('[data-mode]').forEach(group => group.hidden = group.dataset.mode !== mode.value);
    const clearOtherModes = () => {
        const active = mode.value === 'exact' ? ['exact_date'] : mode.value === 'range' ? ['start_date', 'end_date'] : ['month', 'year'];
        keys.filter(key => !active.includes(key)).forEach(key => inputs[key].value = '');
    };
    const moveToInput = () => {
        const date = parse(value('exact_date')) || parse(value('start_date')) || parse(value('end_date'));
        if (mode.value !== 'period' && date) visible = date;
        if (mode.value === 'period') {
            const year = Number(value('year')), month = Number(value('month'));
            if (year >= 1 && year <= 9999) visible.setFullYear(year);
            if (month >= 1 && month <= 12) visible.setMonth(month - 1, 1);
        }
    };
    const render = () => {
        const year = visible.getFullYear(), month = visible.getMonth();
        document.getElementById('calendar-title').textContent = visible.toLocaleDateString('id-ID', {month: 'long', year: 'numeric'});
        const grid = document.getElementById('calendar');
        grid.replaceChildren();
        ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'].forEach(label => {
            const day = document.createElement('span'); day.className = 'weekday'; day.textContent = label; grid.append(day);
        });
        const first = new Date(visible); first.setDate(1);
        for (let i = 0; i < (first.getDay() + 6) % 7; i++) grid.append(document.createElement('span'));
        const last = new Date(first); last.setMonth(month + 1, 0);
        for (let number = 1; number <= last.getDate(); number++) {
            const date = new Date(first); date.setDate(number); const text = format(date);
            const button = document.createElement('button'); button.type = 'button'; button.className = 'day'; button.textContent = number;
            button.setAttribute('aria-label', date.toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}));
            const selected = mode.value === 'exact' ? text === value('exact_date') : mode.value === 'range' ? text === value('start_date') || text === value('end_date') : false;
            button.classList.toggle('selected', selected); button.setAttribute('aria-pressed', String(selected));
            button.classList.toggle('in-range', mode.value === 'range' && value('start_date') && value('end_date') && text > value('start_date') && text < value('end_date'));
            button.addEventListener('click', () => {
                const anchor = mode.value === 'exact' ? value('exact_date') : mode.value === 'range' && !value('end_date') ? value('start_date') : '';
                if (parse(anchor)) {
                    mode.value = 'range'; inputs.start_date.value = anchor < text ? anchor : text; inputs.end_date.value = anchor < text ? text : anchor;
                } else {
                    mode.value = 'exact'; inputs.exact_date.value = text;
                }
                clearOtherModes(); showMode(); render();
            });
            grid.append(button);
        }
    };
    mode.value = infer(); showMode(); moveToInput(); render();
    document.getElementById('open-date-filter').addEventListener('click', () => {
        snapshot = read(); savedMode = mode.value; moveToInput(); render(); modal.showModal();
    });
    const cancel = () => { if (snapshot) keys.forEach(key => inputs[key].value = snapshot[key]); mode.value = savedMode || infer(); showMode(); moveToInput(); render(); modal.close(); };
    ['close-date-filter', 'cancel-date-filter'].forEach(id => document.getElementById(id).addEventListener('click', cancel));
    modal.addEventListener('cancel', event => { event.preventDefault(); cancel(); });
    mode.addEventListener('change', () => { clearOtherModes(); showMode(); moveToInput(); render(); });
    keys.forEach(key => inputs[key].addEventListener('input', () => { moveToInput(); render(); }));
    document.getElementById('clear-date-filter').addEventListener('click', () => { keys.forEach(key => inputs[key].value = ''); mode.value = 'exact'; showMode(); render(); });
    ['previous-month', 'next-month'].forEach((id, index) => document.getElementById(id).addEventListener('click', () => {
        visible.setDate(1); visible.setMonth(visible.getMonth() + (index ? 1 : -1));
        if (visible.getFullYear() < 1) visible.setFullYear(1, 0, 1);
        if (visible.getFullYear() > 9999) visible.setFullYear(9999, 11, 1);
        if (mode.value === 'period') {
            inputs.month.value = visible.getMonth() + 1;
            if (value('year')) inputs.year.value = visible.getFullYear();
        }
        render();
    }));
})();

</script>
</body>
</html>
