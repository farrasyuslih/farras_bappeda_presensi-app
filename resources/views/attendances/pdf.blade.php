<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Presensi Pegawai</title>
    <style>
        @page { margin: 36px 32px 45px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #253047; }
        h1 { font-size: 20px; margin: 0 0 12px; color: #5530a0; }
        p { margin: 5px 0; } .meta { margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 8px 6px; border: 1px solid #dce0e8; text-align: left; word-wrap: break-word; }
        th { background: #eee8fa; } thead { display: table-header-group; }
        tr { page-break-inside: avoid; } small { font-size: 8px; color: #647087; }
        .footer { position: fixed; bottom: -25px; font-size: 8px; color: #647087; }
        .page:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">Presensi pribadi - Seluruh hasil filter | Halaman <span class="page"></span></div>
    <h1>Laporan Presensi Pegawai Bappeda DKI Jakarta</h1>
    <div class="meta">
        <p>Pegawai: {{ $employee->name }}</p>
        <p>Dicetak: {{ $generatedAt->format('d-m-Y H:i:s') }} WIB</p>
        <p>Filter aktif:
            @php($labels = ['name' => 'Nama', 'exact_date' => 'Tanggal tepat', 'start_date' => 'Tanggal awal', 'end_date' => 'Tanggal akhir', 'month' => 'Bulan', 'year' => 'Tahun', 'status' => 'Status'])
            @forelse (array_filter($filters, fn ($value) => $value !== '') as $key => $value)
                {{ $labels[$key] }}: {{ $key === 'status' ? ucfirst($value) : $value }}{{ $loop->last ? '' : ';' }}
            @empty
                Tanpa filter
            @endforelse
        </p>
        <p>Urutan tanggal: {{ ($sorting['direction'] ?: 'desc') === 'asc' ? 'Terlama' : 'Terbaru' }}</p>
        <p>Total hasil: {{ $attendances->count() }}</p>
    </div>
    <table>
        <thead><tr><th style="width: 25%">Nama</th><th style="width: 17%">Tanggal</th><th style="width: 15%">Check-in</th><th style="width: 25%">Check-out</th><th style="width: 18%">Status</th></tr></thead>
        <tbody>
            @forelse ($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->attendance_date }}</td>
                    <td>{{ $attendance->check_in_time ?? '-' }}</td>
                    <td>{{ $attendance->check_out_time ?? '-' }}@if ($attendance->checkoutLabel())<br><small>{{ $attendance->checkoutLabel() }}</small>@endif</td>
                    <td>{{ ucfirst($attendance->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada data presensi sesuai filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
